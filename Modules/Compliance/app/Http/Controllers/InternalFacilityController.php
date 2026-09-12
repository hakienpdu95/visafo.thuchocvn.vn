<?php

namespace Modules\Compliance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Modules\Compliance\Actions\Backend\StoreInternalFacilityAction;
use Modules\Compliance\Actions\Backend\UpdateInternalFacilityAction;
use Modules\Compliance\Data\Requests\InternalFacilityData;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\InternalFacility;
use Modules\Employee\Enums\RecordStatus;
use Modules\Employee\Models\Employee;
use Modules\Product\Enums\DocumentGroupType;
use Modules\Product\Models\DocumentMasterType;
use ZipArchive;

class InternalFacilityController extends Controller
{
    /** Nhóm hồ sơ hiển thị cho cấp Công ty (Khối 1) và cấp Cơ sở (Khối 2) — Nhân sự chuyển sang Khối 3 auto-sync. */
    private const DOCUMENT_GROUPS = [
        DocumentGroupType::LegalFacility->value,
        DocumentGroupType::AttpQuality->value,
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', InternalFacility::class);

        // Khối 1 cần 1 chủ thể "Công ty" cố định — tự khởi tạo nếu tổ chức chưa có,
        // tránh phải bắt user chọn "Cơ sở" thủ công như thiết kế cũ.
        $headquarter = InternalFacility::query()->firstOrCreate(
            ['type' => 'headquarter'],
            ['name' => 'Trụ sở chính (Công ty)', 'status' => 'active']
        );

        $localFacilities = InternalFacility::query()
            ->where('type', '!=', 'headquarter')
            ->orderBy('name')
            ->get();

        $headquarter->load(['documents' => fn ($q) => $q->with('documentType')->latest('issue_date')]);
        $localFacilities->load(['documents' => fn ($q) => $q->with('documentType')->latest('issue_date')]);

        $documentTypes = DocumentMasterType::query()
            ->applicableTo('internal')
            ->whereIn('document_group', self::DOCUMENT_GROUPS)
            ->orderBy('document_group')
            ->orderBy('name')
            ->get();

        $documentTypesByGroup = [
            DocumentGroupType::LegalFacility->value => $documentTypes->where('document_group', DocumentGroupType::LegalFacility)->values(),
            DocumentGroupType::AttpQuality->value   => $documentTypes->where('document_group', DocumentGroupType::AttpQuality)->values(),
        ];

        $employees = Employee::query()
            ->with(['latestHealthCheck', 'latestAttpTraining'])
            ->orderBy('full_name')
            ->get();

        return view('compliance::internal_compliance.index', [
            'headquarter'          => $headquarter,
            'localFacilities'      => $localFacilities,
            'documentTypesByGroup' => $documentTypesByGroup,
            'canManageDocuments'   => auth()->user()->can('compliance.manage'),
            'canManageFacilities'  => auth()->user()->can('create', InternalFacility::class),
            'canViewEmployees'     => auth()->user()->can('employee.view'),
            'employeeStats'        => $this->buildEmployeeStats($employees),
        ]);
    }

    public function store(Request $request, StoreInternalFacilityAction $action): RedirectResponse
    {
        $this->authorize('create', InternalFacility::class);

        $data = InternalFacilityData::validateAndCreate($request->all());
        $facility = $action->handle($data);

        return redirect(route('backend.internal-compliance.index') . '#facility-' . $facility->id)
            ->with('success', 'Đã thêm cơ sở "' . $facility->name . '".');
    }

    public function update(Request $request, InternalFacility $internalFacility, UpdateInternalFacilityAction $action): RedirectResponse
    {
        $this->authorize('update', $internalFacility);

        $data = InternalFacilityData::validateAndCreate($request->all());
        $action->handle($internalFacility, $data);

        return redirect(route('backend.internal-compliance.index') . '#facility-' . $internalFacility->id)
            ->with('success', 'Đã cập nhật cơ sở "' . $internalFacility->name . '".');
    }

    /**
     * Gom toàn bộ file hồ sơ (mọi cơ sở) đang có status=active thành 1 file ZIP
     * để team Sale/Kinh doanh gửi ngay cho đối tác/siêu thị.
     */
    public function export(): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('viewAny', InternalFacility::class);

        $facilities = InternalFacility::query()
            ->with(['documents' => fn ($q) => $q->where('status', ComplianceDocumentStatus::Active->value)->with('documentType')])
            ->get();

        $zipPath = tempnam(sys_get_temp_dir(), 'visafo_profile_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $fileCount = 0;
        foreach ($facilities as $facility) {
            $folder = Str::slug($facility->name) ?: $facility->type;

            foreach ($facility->documents as $document) {
                $media = $document->getFirstMedia('attachments_private');
                if (! $media) {
                    continue;
                }

                $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
                if ($contents === null) {
                    continue;
                }

                $entryName = $folder . '/' . Str::slug($document->documentType->name) . '-' . $document->id . '.' . $media->extension;
                $zip->addFromString($entryName, $contents);
                $fileCount++;
            }
        }
        $zip->close();

        if ($fileCount === 0) {
            @unlink($zipPath);

            return back()->with('error', 'Chưa có hồ sơ nào đang hiệu lực (status = Đang hiệu lực) kèm file để xuất.');
        }

        return response()->download($zipPath, 'ho-so-nang-luc-visafo-' . now()->format('Ymd-His') . '.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return array{total: int, health_valid: int, attp_valid: int, health_valid_pct: int, attp_valid_pct: int, at_risk: array}
     */
    private function buildEmployeeStats(Collection $employees): array
    {
        $total       = $employees->count();
        $healthValid = 0;
        $attpValid   = 0;
        $atRisk      = [];

        foreach ($employees as $employee) {
            $healthStatus = $employee->healthCheckStatus();
            $attpStatus   = $employee->attpTrainingStatus();

            if ($healthStatus === RecordStatus::Valid) {
                $healthValid++;
            }
            if ($attpStatus === RecordStatus::Valid) {
                $attpValid++;
            }

            if ($healthStatus !== RecordStatus::Valid || $attpStatus !== RecordStatus::Valid) {
                $atRisk[] = [
                    'id'                  => $employee->id,
                    'full_name'           => $employee->full_name,
                    'job_title'           => $employee->job_title,
                    'health_status_label' => $healthStatus->label(),
                    'health_status_badge' => $healthStatus->badgeClass(),
                    'attp_status_label'   => $attpStatus->label(),
                    'attp_status_badge'   => $attpStatus->badgeClass(),
                    'edit_url'            => route('backend.employees.edit', $employee),
                    'severity'            => max(self::severityWeight($healthStatus), self::severityWeight($attpStatus)),
                ];
            }
        }

        usort($atRisk, fn ($a, $b) => $b['severity'] <=> $a['severity']);

        return [
            'total'            => $total,
            'health_valid'     => $healthValid,
            'attp_valid'       => $attpValid,
            'health_valid_pct' => $total ? (int) round($healthValid / $total * 100) : 0,
            'attp_valid_pct'   => $total ? (int) round($attpValid / $total * 100) : 0,
            'at_risk'          => array_values($atRisk),
        ];
    }

    private static function severityWeight(RecordStatus $status): int
    {
        return match ($status) {
            RecordStatus::Expired, RecordStatus::Missing => 2,
            RecordStatus::Expiring => 1,
            RecordStatus::Valid    => 0,
        };
    }
}
