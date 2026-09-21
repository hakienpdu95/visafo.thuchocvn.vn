<?php

namespace Modules\FoodInspection\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\FoodInspection\Actions\Backend\DestroyFoodInspectionStep2Action;
use Modules\FoodInspection\Actions\Backend\StoreFoodInspectionStep2Action;
use Modules\FoodInspection\Actions\Backend\UpdateFoodInspectionStep2Action;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep2Data;
use Modules\FoodInspection\Models\FoodInspectionStep2Log;
use Modules\FoodInspection\Queries\GetStep2FormOptionsHandler;
use Modules\FoodInspection\Queries\GetStep2FormOptionsQuery;
use Modules\FoodInspection\Queries\GetStep2LogHandler;
use Modules\FoodInspection\Queries\GetStep2LogQuery;
use Modules\Menu\Queries\FindMenuDishesHandler;
use Modules\Menu\Queries\FindMenuDishesQuery;

class FoodInspectionStep2Controller extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FoodInspectionStep2Log::class, 'food_inspection_step2');
    }

    public function index(GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-inspection-step2.index', $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function create(GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-inspection-step2.create', $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function store(Request $request, StoreFoodInspectionStep2Action $action): RedirectResponse
    {
        $log = $action->handle(StoreFoodInspectionStep2Data::validateAndCreate($request->all()), $request->user());

        return redirect()->route('backend.food-inspection-step2.show', $log)->with('success', 'Đã lưu sổ kiểm thực Bước 2.');
    }

    public function show(FoodInspectionStep2Log $foodInspectionStep2, GetStep2LogHandler $handler)
    {
        return view('foodinspection::food-inspection-step2.show', ['log' => $handler->handle(new GetStep2LogQuery($foodInspectionStep2))]);
    }

    public function edit(FoodInspectionStep2Log $foodInspectionStep2, GetStep2LogHandler $handler, GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-inspection-step2.edit', [
            'log' => $handler->handle(new GetStep2LogQuery($foodInspectionStep2)),
        ] + $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function update(Request $request, FoodInspectionStep2Log $foodInspectionStep2, UpdateFoodInspectionStep2Action $action): RedirectResponse
    {
        $action->handle($foodInspectionStep2, StoreFoodInspectionStep2Data::validateAndCreate($request->all()));

        return redirect()->route('backend.food-inspection-step2.show', $foodInspectionStep2)->with('success', 'Đã cập nhật sổ kiểm thực Bước 2.');
    }

    public function destroy(Request $request, FoodInspectionStep2Log $foodInspectionStep2, DestroyFoodInspectionStep2Action $action): RedirectResponse|JsonResponse
    {
        $label = $action->handle($foodInspectionStep2);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa sổ kiểm thực ' . $label . '.']);
        }

        return redirect()->route('backend.food-inspection-step2.index')->with('success', 'Đã xóa sổ kiểm thực ' . $label . '.');
    }

    /** Đồng bộ Thực đơn: món ăn của cơ sở theo ngày + bữa ăn, để đổ xuống lưới kiểm thực (JSON). */
    public function menuDishes(Request $request, FindMenuDishesHandler $handler): JsonResponse
    {
        $this->authorize('create', FoodInspectionStep2Log::class);

        $params = $request->validate([
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'date'        => ['required', 'date'],
            'meal_time'   => ['required', 'in:breakfast,lunch,afternoon,dinner'],
        ]);

        $menu = $handler->handle(new FindMenuDishesQuery($params['customer_id'], $params['date'], $params['meal_time']));

        return response()->json([
            'found' => $menu !== null,
            'data'  => $menu['dishes'] ?? [],
        ]);
    }
}
