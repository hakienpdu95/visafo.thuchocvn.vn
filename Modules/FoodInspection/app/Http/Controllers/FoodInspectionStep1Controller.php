<?php

namespace Modules\FoodInspection\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\FoodInspection\Actions\Backend\DestroyFoodInspectionStep1Action;
use Modules\FoodInspection\Actions\Backend\StoreFoodInspectionStep1Action;
use Modules\FoodInspection\Actions\Backend\UpdateFoodInspectionStep1Action;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep1Data;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;
use Modules\FoodInspection\Queries\GetFoodInspectionStep1LogHandler;
use Modules\FoodInspection\Queries\GetFilterOptionsHandler;
use Modules\FoodInspection\Queries\GetFilterOptionsQuery;
use Modules\FoodInspection\Queries\GetFoodInspectionStep1LogQuery;
use Modules\FoodInspection\Queries\GetStep1FormOptionsHandler;
use Modules\FoodInspection\Queries\GetStep1FormOptionsQuery;

class FoodInspectionStep1Controller extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FoodInspectionStep1Log::class, 'food_inspection');
    }

    public function index(GetFilterOptionsHandler $filterOptions)
    {
        return view('foodinspection::food-inspections.index', $filterOptions->handle(new GetFilterOptionsQuery()));
    }

    public function create(GetStep1FormOptionsHandler $options)
    {
        return view('foodinspection::food-inspections.create', [
            'options' => $options->handle(new GetStep1FormOptionsQuery()),
        ]);
    }

    public function store(Request $request, StoreFoodInspectionStep1Action $action): RedirectResponse
    {
        $log = $action->handle(StoreFoodInspectionStep1Data::validateAndCreate($request->all()), $request->user()?->id);

        return redirect()->route('backend.food-inspections.show', $log)
            ->with('success', 'Đã lưu sổ kiểm thực Bước 1.');
    }

    public function show(FoodInspectionStep1Log $foodInspection, GetFoodInspectionStep1LogHandler $handler)
    {
        return view('foodinspection::food-inspections.show', [
            'log' => $handler->handle(new GetFoodInspectionStep1LogQuery($foodInspection)),
        ]);
    }

    public function edit(FoodInspectionStep1Log $foodInspection, GetFoodInspectionStep1LogHandler $handler, GetStep1FormOptionsHandler $options)
    {
        return view('foodinspection::food-inspections.edit', [
            'log'     => $handler->handle(new GetFoodInspectionStep1LogQuery($foodInspection)),
            'options' => $options->handle(new GetStep1FormOptionsQuery()),
        ]);
    }

    public function update(Request $request, FoodInspectionStep1Log $foodInspection, UpdateFoodInspectionStep1Action $action): RedirectResponse
    {
        $action->handle($foodInspection, StoreFoodInspectionStep1Data::validateAndCreate($request->all()));

        return redirect()->route('backend.food-inspections.show', $foodInspection)
            ->with('success', 'Đã cập nhật sổ kiểm thực Bước 1.');
    }

    public function destroy(Request $request, FoodInspectionStep1Log $foodInspection, DestroyFoodInspectionStep1Action $action): RedirectResponse|JsonResponse
    {
        $label = $action->handle($foodInspection);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa sổ kiểm thực ' . $label . '.']);
        }

        return redirect()->route('backend.food-inspections.index')
            ->with('success', 'Đã xóa sổ kiểm thực ' . $label . '.');
    }
}
