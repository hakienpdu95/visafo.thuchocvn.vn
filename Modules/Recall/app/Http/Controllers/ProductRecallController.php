<?php

namespace Modules\Recall\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;
use Modules\Recall\Actions\CancelProductRecallAction;
use Modules\Recall\Actions\CompleteProductRecallAction;
use Modules\Recall\Actions\StoreProductRecallAction;
use Modules\Recall\Data\Requests\StoreProductRecallData;
use Modules\Recall\Enums\RecallStatus;
use Modules\Recall\Models\ProductRecall;
use Modules\Recall\Queries\GetProductRecallHandler;
use Modules\Recall\Queries\GetProductRecallQuery;
use Modules\Recall\Queries\GetRecallAffectedCustomersHandler;
use Modules\Recall\Queries\GetRecallAffectedCustomersQuery;
use Modules\Recall\Queries\ListProductRecallsHandler;
use Modules\Recall\Queries\ListProductRecallsQuery;

class ProductRecallController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ProductRecall::class, 'recall');
    }

    public function index(Request $request, ListProductRecallsHandler $handler)
    {
        $recalls = $handler->handle(new ListProductRecallsQuery(
            page:    max(1, (int) $request->integer('page', 1)),
            perPage: 25,
            status:  $request->input('status'),
        ));

        $statuses = collect(RecallStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->all();

        return view('recall::product_recalls.index', compact('recalls', 'statuses'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();

        return view('recall::product_recalls.create', compact('products'));
    }

    public function store(Request $request, StoreProductRecallAction $action): RedirectResponse
    {
        $data   = StoreProductRecallData::validateAndCreate($request->all());
        $recall = $action->handle($data);

        return redirect()->route('backend.product-recalls.show', $recall)
            ->with('success', 'Đã khởi tạo chiến dịch thu hồi.');
    }

    public function show(ProductRecall $recall, GetProductRecallHandler $handler, GetRecallAffectedCustomersHandler $customersHandler)
    {
        $recall = $handler->handle(new GetProductRecallQuery($recall));
        $affectedCustomers = $customersHandler->handle(new GetRecallAffectedCustomersQuery($recall));

        return view('recall::product_recalls.show', compact('recall', 'affectedCustomers'));
    }

    public function complete(ProductRecall $recall, CompleteProductRecallAction $action): RedirectResponse
    {
        $this->authorize('update', $recall);

        $action->handle($recall);

        return redirect()->route('backend.product-recalls.show', $recall)
            ->with('success', 'Đã đánh dấu chiến dịch thu hồi hoàn tất.');
    }

    public function cancel(ProductRecall $recall, CancelProductRecallAction $action): RedirectResponse
    {
        $this->authorize('update', $recall);

        $action->handle($recall);

        return redirect()->route('backend.product-recalls.show', $recall)
            ->with('success', 'Đã hủy chiến dịch thu hồi.');
    }
}
