<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Warehouse\Actions\Backend\ProvisionRetailItemTagsAction;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Models\TagRoll;
use Modules\Warehouse\Queries\GetTagRollAllocationMapHandler;
use Modules\Warehouse\Queries\GetTagRollAllocationMapQuery;
use Modules\Warehouse\Support\QrCodeGenerator;

class TagProvisioningController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        abort_unless(auth()->user()->can('warehouse.manage'), 403);

        $stats = [
            'provisioned' => RetailItemTag::where('status', RetailItemTagStatus::Provisioned->value)->count(),
            'bound'       => RetailItemTag::where('status', '!=', RetailItemTagStatus::Provisioned->value)->count(),
        ];

        $prefixes = TagRoll::query()->distinct()->orderBy('prefix')->pluck('prefix')->filter()->values();

        return view('warehouse::tag_rolls.index', compact('stats', 'prefixes'));
    }

    public function show(TagRoll $roll, GetTagRollAllocationMapHandler $handler): \Illuminate\View\View
    {
        abort_unless(auth()->user()->can('warehouse.manage'), 403);

        $counts   = $roll->liveCounts();
        $segments = $handler->handle(new GetTagRollAllocationMapQuery($roll));

        return view('warehouse::tag_rolls.show', compact('roll', 'counts', 'segments'));
    }

    public function provision(Request $request, ProvisionRetailItemTagsAction $action): RedirectResponse
    {
        abort_unless(auth()->user()->can('warehouse.manage'), 403);

        $validated = $request->validate([
            'count'  => ['required', 'integer', 'min:1', 'max:100000'],
            'prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]*$/'],
        ]);

        $result = $action->handle(
            (int) $validated['count'],
            (string) ($validated['prefix'] ?? ''),
        );

        return redirect()->route('backend.tag-rolls.index')
            ->with('success', "Đã in {$result['count']} tem mới — prefix \"{$result['prefix']}\" — dải số visual_sequence từ {$result['from']} đến {$result['to']}.")
            ->with('provisioned_range', $result);
    }

    public function downloadCsv(Request $request): Response
    {
        abort_unless(auth()->user()->can('warehouse.manage'), 403);

        $roll = $this->resolveRollForRange($request);
        [$from, $to] = [(int) $request->input('from'), (int) $request->input('to')];

        $tags = RetailItemTag::whereBetween('visual_sequence', [$from, $to])
            ->orderBy('visual_sequence')
            ->get(['uid', 'gs1_serial', 'visual_sequence', 'qr_code']);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['visual_sequence', 'gs1_serial', 'uid', 'qr_code'], ',', '"', '\\');
        foreach ($tags as $tag) {
            fputcsv($handle, [$tag->visual_sequence, $tag->gs1_serial, $tag->uid, $tag->qr_code], ',', '"', '\\');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"tem-roll-{$roll->prefix}-{$from}-{$to}.csv\"",
        ]);
    }

    public function downloadPdf(Request $request, QrCodeGenerator $qrCodeGenerator)
    {
        abort_unless(auth()->user()->can('warehouse.manage'), 403);

        $roll = $this->resolveRollForRange($request);
        [$from, $to] = [(int) $request->input('from'), (int) $request->input('to')];

        $tags = RetailItemTag::whereBetween('visual_sequence', [$from, $to])
            ->orderBy('visual_sequence')
            ->get(['gs1_serial', 'visual_sequence', 'qr_code'])
            ->map(fn ($tag) => [
                'gs1_serial'      => $tag->gs1_serial,
                'visual_sequence' => $tag->visual_sequence,
                'svg'             => $qrCodeGenerator->toSvg($tag->qr_code, 160),
            ]);

        return \Spatie\LaravelPdf\Facades\Pdf::view('warehouse::tag_rolls.print', ['tags' => $tags])
            ->format('a4')
            ->withBrowsershot(fn ($b) => $b->setChromePath(config('warehouse.chrome_path', '/usr/bin/google-chrome'))->noSandbox())
            ->download("tem-roll-{$roll->prefix}-{$from}-{$to}.pdf");
    }

    /**
     * Bắt buộc chọn đúng prefix của cuộn — chống nhầm cuộn khi 2 cuộn khác nhau
     * có thể vô tình được thủ kho gõ trùng khoảng số (dù visual_sequence không bao giờ
     * trùng thật giữa các cuộn, việc bắt khớp prefix vẫn chặn được lỗi gõ nhầm số sớm).
     */
    private function resolveRollForRange(Request $request): TagRoll
    {
        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:10'],
            'from'   => ['required', 'integer', 'min:1'],
            'to'     => ['required', 'integer', 'min:1', 'gte:from'],
        ]);

        $prefix = strtoupper($validated['prefix']);

        $roll = TagRoll::where('prefix', $prefix)
            ->where('from_sequence', '<=', $validated['from'])
            ->where('to_sequence', '>=', $validated['to'])
            ->first();

        if (! $roll) {
            throw ValidationException::withMessages([
                'prefix' => "Không tìm thấy cuộn tem nào có prefix \"{$prefix}\" chứa trọn dải {$validated['from']}–{$validated['to']}. Vui lòng kiểm tra lại prefix và dải số.",
            ]);
        }

        return $roll;
    }
}
