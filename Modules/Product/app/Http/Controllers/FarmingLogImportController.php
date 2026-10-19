<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Product\Actions\Backend\ImportFarmingLogsAction;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Support\VietGapLogParser;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FarmingLogImportController extends Controller
{
    public const DISK = 'local';
    public const DIR = 'farming-log-imports';

    public function template(): BinaryFileResponse
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);

        return response()->download(
            module_path('Product', 'resources/templates/nhat-ky-vietgap-mau.xlsx'),
            'Mau-nhat-ky-VietGAP.xlsx'
        );
    }

    public function preview(Request $request, VietGapLogParser $parser): JsonResponse
    {
        abort_unless($request->user()->can('compliance.manage'), 403);

        $request->validate([
            'file'  => ['required_without:token', 'nullable', 'file', 'mimes:xlsx', 'max:10240'],
            'token' => ['required_without:file', 'nullable', 'string', 'regex:/^[0-9a-z]{26}$/'],
        ], [
            'file.required_without' => 'Vui lòng chọn file Excel nhật ký.',
            'file.mimes'            => 'Chỉ chấp nhận file .xlsx.',
            'file.max'              => 'Dung lượng file không được vượt quá 10MB.',
        ]);

        if ($request->hasFile('file')) {
            $token = Str::lower((string) Str::ulid());
            $storedPath = $request->file('file')->storeAs(self::DIR, $token . '.xlsx', self::DISK);
        } else {
            $token = $request->input('token');
            $storedPath = self::DIR . '/' . $token . '.xlsx';
            if (!Storage::disk(self::DISK)->exists($storedPath)) {
                return response()->json(['message' => 'Phiên import đã hết hạn, vui lòng tải file lên lại.'], 422);
            }
        }

        $fullPath = Storage::disk(self::DISK)->path($storedPath);

        try {
            $sheets = collect($parser->sheetSummaries($fullPath))
                ->map(function (array $sheet) use ($parser, $fullPath) {
                    $parsed = $parser->parse($fullPath, $sheet['name']);

                    return [...$sheet, 'rows' => count($parsed['rows']), 'errors' => count($parsed['errors'])];
                })
                ->all();
        } catch (\Throwable) {
            Storage::disk(self::DISK)->delete($storedPath);

            return response()->json(['message' => 'Không đọc được file Excel. Vui lòng kiểm tra lại định dạng .xlsx.'], 422);
        }

        $batches = $this->candidateBatches();
        $suggestions = $this->suggest($sheets, $batches);

        return response()->json([
            'token'   => $token,
            'sheets'  => collect($sheets)->map(fn (array $s) => [
                ...$s,
                'sowing_date_text' => $s['sowing_date'] ? Carbon::parse($s['sowing_date'])->format('d/m/Y') : null,
                'suggestion'       => $suggestions[$s['name']] ?? null,
            ])->values(),
            'batches' => $batches->map(fn (FarmingBatch $b) => [
                'id'     => $b->id,
                'status' => $b->status,
                'label'  => implode(' - ', array_filter([
                    $b->batch_code,
                    $b->vendor?->name,
                    $b->partnerProduct?->name ?? Str::limit((string) $b->agriSeed?->name, 30),
                ])) . ($b->sowing_date ? ' (Gieo: ' . $b->sowing_date->format('d/m/Y') . ')' : ''),
            ])->values(),
        ]);
    }

    public function store(Request $request, VietGapLogParser $parser, ImportFarmingLogsAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('compliance.manage'), 403);

        $data = validator($request->all(), [
            'token'               => ['required', 'string', 'regex:/^[0-9a-z]{26}$/'],
            'mappings'            => ['required', 'array'],
            'mappings.*.sheet'    => ['required', 'string', 'max:100'],
            'mappings.*.batch_id' => ['nullable', 'string', 'exists:farming_batches,id'],
            'return_batch_id'     => ['nullable', 'string'],
        ], [
            'mappings.*.batch_id.exists' => 'Lô được chọn không hợp lệ.',
        ]);

        $returnBatch = $request->filled('return_batch_id')
            ? FarmingBatch::query()->find($request->input('return_batch_id'))
            : null;
        $back = $returnBatch
            ? redirect()->route('backend.farming-batches.show', $returnBatch)
            : redirect()->route('backend.farming-batches.index');

        if ($data->fails()) {
            return $back->with('error', $data->errors()->first());
        }

        $data = $data->validated();
        $storedPath = self::DIR . '/' . $data['token'] . '.xlsx';

        if (!Storage::disk(self::DISK)->exists($storedPath)) {
            return $back->with('error', 'Phiên import đã hết hạn, vui lòng tải file lên lại.');
        }

        $mappings = collect($data['mappings'])->filter(fn (array $m) => !empty($m['batch_id']))->values();

        if ($mappings->isEmpty()) {
            return $back->with('error', 'Vui lòng chọn lô đích cho ít nhất một sheet.');
        }

        if ($mappings->pluck('batch_id')->duplicates()->isNotEmpty()) {
            return $back->with('error', 'Mỗi lô chỉ được ánh xạ với một sheet.');
        }

        $fullPath = Storage::disk(self::DISK)->path($storedPath);
        $sheetNames = array_column($parser->sheetSummaries($fullPath), 'name');
        $batches = FarmingBatch::query()->whereIn('id', $mappings->pluck('batch_id'))->get()->keyBy('id');

        $results = [];
        foreach ($mappings as $mapping) {
            $sheetName = collect($sheetNames)->first(fn (string $n) => trim($n) === trim($mapping['sheet']));
            $batch = $batches->get($mapping['batch_id']);

            if ($sheetName === null || $batch === null || $batch->status === 'cancelled') {
                continue;
            }

            $this->authorize('update', $batch);

            $results[] = [
                ...$action->handle($batch, $fullPath, $sheetName, $request->user()->id),
                'sheet'      => trim($sheetName),
                'batch_code' => $batch->batch_code,
                'batch_url'  => route('backend.farming-batches.show', $batch),
            ];
        }

        Storage::disk(self::DISK)->delete($storedPath);

        return $back->with('log_import_results', $results);
    }

    public function discard(Request $request, string $token): JsonResponse
    {
        abort_unless($request->user()->can('compliance.manage'), 403);
        abort_unless(preg_match('/^[0-9a-z]{26}$/', $token) === 1, 404);

        Storage::disk(self::DISK)->delete(self::DIR . '/' . $token . '.xlsx');

        return response()->json(['message' => 'Đã hủy phiên import.']);
    }

    private function candidateBatches(): Collection
    {
        return FarmingBatch::query()
            ->where('status', '!=', 'cancelled')
            ->with(['vendor:id,name', 'agriSeed:id,name', 'partnerProduct:id,name'])
            ->orderByDesc('sowing_date')
            ->get();
    }

    private function suggest(array $sheets, Collection $batches): array
    {
        $candidates = [];

        foreach ($sheets as $sheet) {
            foreach ($batches as $batch) {
                $sameDate = $sheet['sowing_date'] && $batch->sowing_date?->toDateString() === $sheet['sowing_date'];
                $sameOwner = $this->similar($sheet['owner'], $batch->vendor?->name);
                $sameCrop = $this->sameCrop($sheet, $batch);

                $level = match (true) {
                    $sameDate && $sameOwner && $sameCrop => ['score' => 40, 'level' => 'exact', 'note' => 'Khớp nông hộ, ngày gieo và mặt hàng'],
                    $sameDate && $sameOwner              => ['score' => 30, 'level' => 'date_owner', 'note' => 'Khớp nông hộ và ngày gieo'],
                    $sameDate && $sameCrop               => ['score' => 25, 'level' => 'date_crop', 'note' => 'Khớp ngày gieo và mặt hàng'],
                    $sameDate                            => ['score' => 20, 'level' => 'date', 'note' => 'Khớp ngày gieo'],
                    $sameOwner && $sameCrop              => ['score' => 10, 'level' => 'owner_crop', 'note' => 'Khớp nông hộ và mặt hàng nhưng lệch ngày gieo (Excel '
                        . ($sheet['sowing_date'] ? Carbon::parse($sheet['sowing_date'])->format('d/m/Y') : 'không rõ')
                        . ', lô ' . ($batch->sowing_date?->format('d/m/Y') ?? 'không rõ') . ') — kiểm tra lại trước khi import'],
                    default                              => null,
                };

                if ($level !== null) {
                    $candidates[] = ['sheet' => $sheet['name'], 'batch_id' => $batch->id, ...$level];
                }
            }
        }

        $candidates = collect($candidates);
        $taken = [];
        $result = [];

        foreach ($candidates->sortByDesc('score') as $candidate) {
            if (isset($result[$candidate['sheet']]) || isset($taken[$candidate['batch_id']])) {
                continue;
            }

            $rivals = $candidates
                ->where('sheet', $candidate['sheet'])
                ->where('score', $candidate['score'])
                ->reject(fn (array $c) => isset($taken[$c['batch_id']]));
            if ($rivals->count() > 1) {
                continue;
            }

            $result[$candidate['sheet']] = collect($candidate)->only('batch_id', 'level', 'note')->all();
            $taken[$candidate['batch_id']] = true;
        }

        return $result;
    }

    private function sameCrop(array $sheet, FarmingBatch $batch): bool
    {
        $sheetText = $this->normalize($sheet['name'] . ' ' . $sheet['crop']);

        foreach ([$batch->partnerProduct?->name, $batch->agriSeed?->name] as $name) {
            $name = $this->normalize((string) $name);
            if ($name !== '' && mb_strlen($name) <= 40 && str_contains($sheetText, $name)) {
                return true;
            }
        }

        $crop = $this->normalize((string) $sheet['crop']);

        return $crop !== '' && $crop === $this->normalize((string) $batch->agriSeed?->name);
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii($value))));
    }

    private function similar(?string $a, ?string $b): bool
    {
        $words = fn (?string $v) => collect(preg_split('/\s+/', Str::lower(Str::ascii((string) $v))))
            ->filter(fn (string $w) => mb_strlen($w) >= 2)
            ->unique();

        $wa = $words($a);
        $wb = $words($b);

        if ($wa->isEmpty() || $wb->isEmpty()) {
            return false;
        }

        return $wa->intersect($wb)->count() >= min(2, $wa->count());
    }
}
