<?php

namespace Modules\FoodInspection\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\FoodInspection\Actions\Backend\DestroyFoodInspectionStep3Action;
use Modules\FoodInspection\Actions\Backend\StoreFoodInspectionStep3Action;
use Modules\FoodInspection\Actions\Backend\UpdateFoodInspectionStep3Action;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep3Data;
use Modules\FoodInspection\Models\FoodInspectionStep3Log;
use Modules\FoodInspection\Queries\FindStep3SourceDishesHandler;
use Modules\FoodInspection\Queries\FindStep3SourceDishesQuery;
use Modules\FoodInspection\Queries\GetStep2FormOptionsHandler;
use Modules\FoodInspection\Queries\GetStep2FormOptionsQuery;
use Modules\FoodInspection\Queries\GetStep3LogHandler;
use Modules\FoodInspection\Queries\GetStep3LogQuery;

class FoodInspectionStep3Controller extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FoodInspectionStep3Log::class, 'food_inspection_step3');
    }

    public function index(GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-inspection-step3.index', $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function create(GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-inspection-step3.create', $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function store(Request $request, StoreFoodInspectionStep3Action $action): RedirectResponse
    {
        $log = $action->handle(StoreFoodInspectionStep3Data::validateAndCreate($request->all()), $request->user());

        return redirect()->route('backend.food-inspection-step3.show', $log)->with('success', 'Đã lưu sổ kiểm thực Bước 2.');
    }

    public function show(FoodInspectionStep3Log $foodInspectionStep3, GetStep3LogHandler $handler)
    {
        return view('foodinspection::food-inspection-step3.show', ['log' => $handler->handle(new GetStep3LogQuery($foodInspectionStep3))]);
    }

    public function edit(FoodInspectionStep3Log $foodInspectionStep3, GetStep3LogHandler $handler, GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-inspection-step3.edit', [
            'log' => $handler->handle(new GetStep3LogQuery($foodInspectionStep3)),
        ] + $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function update(Request $request, FoodInspectionStep3Log $foodInspectionStep3, UpdateFoodInspectionStep3Action $action): RedirectResponse
    {
        $action->handle($foodInspectionStep3, StoreFoodInspectionStep3Data::validateAndCreate($request->all()));

        return redirect()->route('backend.food-inspection-step3.show', $foodInspectionStep3)->with('success', 'Đã cập nhật sổ kiểm thực Bước 2.');
    }

    public function destroy(Request $request, FoodInspectionStep3Log $foodInspectionStep3, DestroyFoodInspectionStep3Action $action): RedirectResponse|JsonResponse
    {
        $label = $action->handle($foodInspectionStep3);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa sổ kiểm thực ' . $label . '.']);
        }

        return redirect()->route('backend.food-inspection-step3.index')->with('success', 'Đã xóa sổ kiểm thực ' . $label . '.');
    }

    /** Đồng bộ từ Bước 2: món đã nấu (đạt) + món ăn sẵn/tráng miệng từ Thực đơn, theo khách hàng + ngày + bữa ăn (JSON). */
    public function sourceDishes(Request $request, FindStep3SourceDishesHandler $handler): JsonResponse
    {
        $this->authorize('create', FoodInspectionStep3Log::class);

        $params = $request->validate([
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'date'        => ['required', 'date'],
            'meal_time'   => ['required', 'in:breakfast,lunch,afternoon,dinner'],
        ]);

        $result = $handler->handle(new FindStep3SourceDishesQuery($params['customer_id'], $params['date'], $params['meal_time']));

        return response()->json([
            'found'      => $result['found'],
            'data'       => $result['rows'],
            'from_step2' => $result['from_step2'],
            'from_menu'  => $result['from_menu'],
        ]);
    }
}
