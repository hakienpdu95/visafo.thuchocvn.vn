<?php

namespace Modules\Compliance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\ChunkedUploadService;
use App\Services\Media\MediaUrlService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Compliance\Actions\Backend\DestroyComplianceDocumentAction;
use Modules\Compliance\Actions\Backend\DestroyComplianceDocumentMediaAction;
use Modules\Compliance\Actions\Backend\StoreComplianceDocumentAction;
use Modules\Compliance\Actions\Backend\UpdateComplianceDocumentAction;
use Modules\Compliance\Actions\Backend\UpdateSharedComplianceDocumentAction;
use Modules\Compliance\Actions\Backend\UploadSharedComplianceDocumentAction;
use Modules\Compliance\Data\Requests\StoreComplianceDocumentData;
use Modules\Compliance\Data\Requests\StoreSharedComplianceDocumentData;
use Modules\Compliance\Data\Requests\UpdateSharedComplianceDocumentData;
use Modules\Compliance\Enums\SharedDocumentCategory;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Compliance\Models\InternalFacility;
use Modules\Compliance\Support\ComplianceDocumentZip;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;
use Spatie\MediaLibrary\Support\MediaStream;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceDocumentController extends Controller
{
    private const BULK_DOWNLOAD_LIMIT = 100;

    private const DOCUMENTABLE_LABELS = [
        'vendor'             => 'Nhà cung cấp',
        'product'            => 'Sản phẩm Visafo',
        'partner_product'    => 'Hàng hóa NCC',
        'internal_facility'  => 'Cơ sở nội bộ',
        'shared'             => 'Nội bộ dùng chung',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ComplianceDocument::class);

        $documentableTypes = collect(self::DOCUMENTABLE_LABELS)
            ->map(fn ($label, $value) => ['value' => $value, 'text' => $label])
            ->values()
            ->all();

        $sharedCategories = collect(SharedDocumentCategory::cases())
            ->map(fn ($c) => ['value' => $c->value, 'text' => $c->label()])
            ->all();

        return view('compliance::documents.index', [
            'documentableTypes' => $documentableTypes,
            'sharedCategories'  => $sharedCategories,
            'canUploadShared'   => auth()->user()->can('create', ComplianceDocument::class),
        ]);
    }

    public function uploadShared(Request $request, UploadSharedComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('create', ComplianceDocument::class);

        $data = StoreSharedComplianceDocumentData::validateAndCreate($request->all());
        $action->handle($data);

        return redirect()->route('backend.document-repository.index')
            ->with('success', 'Đã tải lên tài liệu nội bộ dùng chung.');
    }

    public function download(ComplianceDocument $document): Responsable|RedirectResponse
    {
        $this->authorize('view', $document);

        $back = fn (string $message) => redirect()->back(fallback: route('backend.document-repository.index'))->with('error', $message);
        $title = $document->documentType?->name ?? $document->custom_name ?? 'Tài liệu';

        $media = $document->getMedia('attachments_private');
        if ($media->isEmpty()) {
            return $back('Giấy tờ "' . $title . '" chưa có tệp đính kèm.');
        }

        $media = $media->filter(fn (Media $m) => Storage::disk($m->disk)->exists($m->getPathRelativeToRoot()))->values();
        if ($media->isEmpty()) {
            return $back('Tệp tin của giấy tờ "' . $title . '" không còn tồn tại trên hệ thống.');
        }

        if ($media->count() === 1) {
            return $media->first();
        }

        $baseName = Str::slug($document->documentType?->name ?? $document->custom_name ?? 'tai-lieu');
        $suffix = Str::slug((string) $document->document_number) ?: Str::upper(substr($document->id, -6));

        return MediaStream::create(($baseName ?: 'tai-lieu') . '_' . $suffix . '.zip')->addMedia($media);
    }

    public function downloadMany(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorize('viewAny', ComplianceDocument::class);

        $ids = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:' . self::BULK_DOWNLOAD_LIMIT],
            'ids.*' => ['string', 'max:26'],
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một giấy tờ.',
            'ids.max'      => 'Chỉ tải tối đa ' . self::BULK_DOWNLOAD_LIMIT . ' giấy tờ mỗi lần.',
        ])['ids'];

        $documents = ComplianceDocument::query()->visibleTo()
            ->whereIn('id', $ids)
            ->with(['documentType', 'documentable', 'media'])
            ->get()
            ->filter(fn (ComplianceDocument $d) => $request->user()->can('view', $d));

        $zip = new ComplianceDocumentZip('kho-tai-lieu_' . now()->format('Ymd-His') . '.zip');
        foreach ($documents as $document) {
            $zip->add($document, ComplianceDocumentZip::label($document));
        }

        $notFound = count($ids) - $documents->count();
        if ($notFound > 0) {
            $zip->skip($notFound . ' giấy tờ không tồn tại hoặc bạn không có quyền xem');
        }

        if ($zip->isEmpty()) {
            return redirect()->route('backend.document-repository.index')
                ->with('error', 'Các giấy tờ đã chọn không có tệp đính kèm nào để tải.');
        }

        return $zip->response('DANH SÁCH GIẤY TỜ', $request->user()->name);
    }

    public function downloadForVendor(Request $request, Vendor $vendor): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $vendor);

        $vendor->load([
            'documents' => fn ($q) => $q->visibleTo()->with(['documentType', 'media']),
            'partnerProducts.documents' => fn ($q) => $q->visibleTo()->with(['documentType', 'media']),
        ]);

        $code = Str::slug((string) $vendor->vendor_code) ?: Str::upper(substr($vendor->id, -6));
        $zip = new ComplianceDocumentZip('ho-so-ncc_' . (Str::slug($vendor->name) ?: 'ncc') . '_' . $code . '.zip');

        foreach ($vendor->documents as $document) {
            if ($request->user()->can('view', $document)) {
                $zip->add($document, ComplianceDocumentZip::label($document, false));
            }
        }

        foreach ($vendor->partnerProducts as $partnerProduct) {
            foreach ($partnerProduct->documents as $document) {
                if ($request->user()->can('view', $document)) {
                    $zip->add($document, ComplianceDocumentZip::label($document, false), 'Hàng hóa - ' . $partnerProduct->name);
                }
            }
        }

        if ($zip->isEmpty()) {
            return redirect()->back(fallback: route('backend.vendors.index'))
                ->with('error', 'Nhà cung cấp "' . $vendor->name . '" chưa có hồ sơ/chứng chỉ nào có tệp đính kèm để tải.');
        }

        return $zip->response('HỒ SƠ NHÀ CUNG CẤP: ' . $vendor->name, $request->user()->name);
    }

    public function editShared(ComplianceDocument $document, MediaUrlService $urlService): JsonResponse
    {
        $this->authorize('update', $document);
        abort_unless($document->documentable_type === null, 404);

        return response()->json([
            'id'              => $document->id,
            'custom_name'     => $document->custom_name,
            'custom_category' => $document->custom_category?->value,
            'notes'           => $document->notes,
            'media'           => $document->getMedia('attachments_private')->map(fn ($m) => [
                'id'         => $m->id,
                'name'       => $m->file_name,
                'size'       => $m->size,
                'is_image'   => str_starts_with($m->mime_type, 'image/'),
                'url'        => $urlService->url($m),
                'delete_url' => route('backend.document-repository.media.destroy', [$document, $m]),
            ])->values(),
        ]);
    }

    public function destroyMediaShared(ComplianceDocument $document, Media $media, DestroyComplianceDocumentMediaAction $action): JsonResponse
    {
        $this->authorize('update', $document);
        abort_unless($document->documentable_type === null, 404);

        $action->handle($document, $media);

        return response()->json(['ok' => true]);
    }

    public function updateShared(Request $request, ComplianceDocument $document, UpdateSharedComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $document);
        abort_unless($document->documentable_type === null, 404);

        $data = UpdateSharedComplianceDocumentData::validateAndCreate($request->all());
        $action->handle($document, $data);

        return redirect()->route('backend.document-repository.index')
            ->with('success', 'Đã cập nhật tài liệu nội bộ dùng chung.');
    }

    public function destroyShared(ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('delete', $document);
        abort_unless($document->documentable_type === null, 404);

        $action->handle($document);

        return redirect()->route('backend.document-repository.index')
            ->with('success', 'Đã xóa tài liệu nội bộ dùng chung.');
    }

    public function storeForVendor(Request $request, Vendor $vendor, StoreComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $vendor);

        return $this->store($request, $vendor, $action, 'backend.vendors.show');
    }

    public function updateForVendor(Request $request, Vendor $vendor, ComplianceDocument $document, UpdateComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $vendor);

        return $this->update($request, $document, $action, 'backend.vendors.show', $vendor);
    }

    public function destroyForVendor(Vendor $vendor, ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $vendor);

        return $this->destroy($document, $action, 'backend.vendors.show', $vendor);
    }

    public function destroyMediaForVendor(Vendor $vendor, ComplianceDocument $document, Media $media, DestroyComplianceDocumentMediaAction $action): JsonResponse
    {
        $this->authorize('update', $vendor);
        abort_unless($document->documentable_id === $vendor->id && $document->documentable_type === $vendor->getMorphClass(), 404);

        $action->handle($document, $media);

        return response()->json(['ok' => true]);
    }

    public function storeForProduct(Request $request, Product $product, StoreComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $product);

        return $this->store($request, $product, $action, 'backend.products.edit');
    }

    public function destroyForProduct(Product $product, ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $product);

        return $this->destroy($document, $action, 'backend.products.edit', $product);
    }

    public function storeForPartnerProduct(Request $request, PartnerProduct $partnerProduct, StoreComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $partnerProduct);

        return $this->store($request, $partnerProduct, $action, 'backend.partner-products.show');
    }

    public function updateForPartnerProduct(Request $request, PartnerProduct $partnerProduct, ComplianceDocument $document, UpdateComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $partnerProduct);

        return $this->update($request, $document, $action, 'backend.partner-products.show', $partnerProduct);
    }

    public function destroyForPartnerProduct(PartnerProduct $partnerProduct, ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $partnerProduct);

        return $this->destroy($document, $action, 'backend.partner-products.show', $partnerProduct);
    }

    public function destroyMediaForPartnerProduct(PartnerProduct $partnerProduct, ComplianceDocument $document, Media $media, DestroyComplianceDocumentMediaAction $action): JsonResponse
    {
        $this->authorize('update', $partnerProduct);
        abort_unless($document->documentable_id === $partnerProduct->id && $document->documentable_type === $partnerProduct->getMorphClass(), 404);

        $action->handle($document, $media);

        return response()->json(['ok' => true]);
    }

    public function storeForInternalFacility(Request $request, InternalFacility $internalFacility, StoreComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $internalFacility);

        $data = StoreComplianceDocumentData::validateAndCreate($this->documentPayload($request));
        $action->handle($internalFacility, $data);

        return $this->backToFacility($internalFacility, 'Đã thêm hồ sơ mới.');
    }

    public function updateForInternalFacility(Request $request, InternalFacility $internalFacility, ComplianceDocument $document, UpdateComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $internalFacility);

        $data = StoreComplianceDocumentData::validateAndCreate($this->documentPayload($request));
        $action->handle($document, $data);

        return $this->backToFacility($internalFacility, 'Đã cập nhật hồ sơ.');
    }

    public function destroyForInternalFacility(InternalFacility $internalFacility, ComplianceDocument $document, DestroyComplianceDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $internalFacility);

        $action->handle($document);

        return $this->backToFacility($internalFacility, 'Đã xóa hồ sơ.');
    }

    public function destroyMediaForInternalFacility(InternalFacility $internalFacility, ComplianceDocument $document, Media $media, DestroyComplianceDocumentMediaAction $action): JsonResponse
    {
        $this->authorize('update', $internalFacility);

        $action->handle($document, $media);

        return response()->json(['ok' => true]);
    }

    /**
     * Trang internal-compliance không còn dropdown "Cơ sở" — dùng #facility-{id}
     * để accordion tự mở đúng khối vừa thao tác sau khi redirect.
     */
    private function backToFacility(InternalFacility $internalFacility, string $message): RedirectResponse
    {
        return redirect(route('backend.internal-compliance.index') . '#facility-' . $internalFacility->id)
            ->with('success', $message);
    }

    private function documentPayload(Request $request): array
    {
        return app(ChunkedUploadService::class)->mergeIntoInput($request, $request->all());
    }

    private function store(Request $request, Model $documentable, StoreComplianceDocumentAction $action, string $redirectRoute, array $redirectParams = []): RedirectResponse
    {
        $data = StoreComplianceDocumentData::validateAndCreate($this->documentPayload($request));
        $action->handle($documentable, $data);

        return redirect()->route($redirectRoute, $redirectParams ?: [$documentable])
            ->with('success', 'Đã thêm hồ sơ mới.');
    }

    private function update(Request $request, ComplianceDocument $document, UpdateComplianceDocumentAction $action, string $redirectRoute, Model $documentable, array $redirectParams = []): RedirectResponse
    {
        $data = StoreComplianceDocumentData::validateAndCreate($this->documentPayload($request));
        $action->handle($document, $data);

        return redirect()->route($redirectRoute, $redirectParams ?: [$documentable])
            ->with('success', 'Đã cập nhật hồ sơ.');
    }

    private function destroy(ComplianceDocument $document, DestroyComplianceDocumentAction $action, string $redirectRoute, Model $documentable, array $redirectParams = []): RedirectResponse
    {
        $action->handle($document);

        return redirect()->route($redirectRoute, $redirectParams ?: [$documentable])
            ->with('success', 'Đã xóa hồ sơ.');
    }
}
