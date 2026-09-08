<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Warehouse\Actions\Backend\UnbindRetailItemTagAction;
use Modules\Warehouse\Actions\Backend\VoidRetailItemTagAction;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Support\QrCodeGenerator;
use Spatie\LaravelPdf\Facades\Pdf;

class RetailItemTagController extends Controller
{
    public function index(Batch $batch)
    {
        $this->authorize('view', $batch);

        $tagsTotal = $batch->tags()->count();

        return view('warehouse::batches.tags_index', compact('batch', 'tagsTotal'));
    }

    public function print(Batch $batch, QrCodeGenerator $qrCodeGenerator)
    {
        $this->authorize('view', $batch);

        $tags = $batch->tags()->orderBy('serial_number')->get()->map(fn ($tag) => [
            'qr_code'    => $tag->qr_code,
            'gs1_serial' => $tag->gs1_serial ?? $tag->serial_number,
            'svg'        => $qrCodeGenerator->toSvg($tag->qr_code, 160),
        ]);

        return Pdf::view('warehouse::batches.tags_print', compact('batch', 'tags'))
            ->format('a4')
            ->withBrowsershot(fn ($browsershot) => $browsershot->setChromePath(config('warehouse.chrome_path', '/usr/bin/google-chrome'))->noSandbox())
            ->download('tem-truy-vet-' . $batch->internal_batch_code . '.pdf');
    }

    public function unbind(RetailItemTag $tag, UnbindRetailItemTagAction $action): RedirectResponse
    {
        abort_unless($tag->batch ? auth()->user()->can('update', $tag->batch) : auth()->user()->can('warehouse.manage'), 403);

        $action->handle($tag);

        return back()->with('success', 'Đã gỡ gắn kết tem — tem đã quay về kho tem tiền định danh (provisioned).');
    }

    public function void(RetailItemTag $tag, VoidRetailItemTagAction $action): RedirectResponse
    {
        abort_unless($tag->batch ? auth()->user()->can('update', $tag->batch) : auth()->user()->can('warehouse.manage'), 403);

        $action->handle($tag);

        return back()->with('success', 'Đã báo hỏng tem — tem bị loại khỏi vòng đời luân chuyển.');
    }
}
