<?php

namespace Modules\FoodInspection\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\FoodInspection\Actions\Backend\DestroyFoodSampleAction;
use Modules\FoodInspection\Actions\Backend\StoreFoodSampleAction;
use Modules\FoodInspection\Actions\Backend\UpdateFoodSampleAction;
use Modules\FoodInspection\Data\Requests\StoreFoodSampleData;
use Modules\FoodInspection\Models\FoodSampleDetail;
use Modules\FoodInspection\Models\FoodSampleLog;
use Modules\FoodInspection\Queries\FindSampleSourceDishesHandler;
use Modules\FoodInspection\Queries\FindSampleSourceDishesQuery;
use Modules\FoodInspection\Queries\GetFoodSampleLogHandler;
use Modules\FoodInspection\Queries\GetFoodSampleLogQuery;
use Modules\FoodInspection\Queries\GetStep2FormOptionsHandler;
use Modules\FoodInspection\Queries\GetStep2FormOptionsQuery;
use Modules\FoodInspection\Support\StorageEquipmentSuggestions;

class FoodSampleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(FoodSampleLog::class, 'food_sample');
    }

    public function index(GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-samples.index', $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function create(GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-samples.create', $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function store(Request $request, StoreFoodSampleAction $action): RedirectResponse
    {
        $log = $action->handle(StoreFoodSampleData::validateAndCreate($request->all()), $request->user());

        return redirect()->route('backend.food-samples.show', $log)
            ->with('success', 'Đã lưu mẫu thức ăn. Bấm "In tem lưu mẫu" để in nhãn dán lên hộp mẫu.');
    }

    public function show(FoodSampleLog $foodSample, GetFoodSampleLogHandler $handler)
    {
        return view('foodinspection::food-samples.show', ['log' => $handler->handle(new GetFoodSampleLogQuery($foodSample))]);
    }

    public function edit(FoodSampleLog $foodSample, GetFoodSampleLogHandler $handler, GetStep2FormOptionsHandler $options)
    {
        return view('foodinspection::food-samples.edit', [
            'log' => $handler->handle(new GetFoodSampleLogQuery($foodSample)),
        ] + $options->handle(new GetStep2FormOptionsQuery()));
    }

    public function update(Request $request, FoodSampleLog $foodSample, UpdateFoodSampleAction $action): RedirectResponse
    {
        $action->handle($foodSample, StoreFoodSampleData::validateAndCreate($request->all()));

        return redirect()->route('backend.food-samples.show', $foodSample)->with('success', 'Đã cập nhật phiếu lưu mẫu.');
    }

    public function destroy(Request $request, FoodSampleLog $foodSample, DestroyFoodSampleAction $action): RedirectResponse|JsonResponse
    {
        $label = $action->handle($foodSample);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa phiếu lưu mẫu ' . $label . '.']);
        }

        return redirect()->route('backend.food-samples.index')->with('success', 'Đã xóa phiếu lưu mẫu ' . $label . '.');
    }

    /** Kế thừa món cần lưu mẫu từ Sổ Bước 3 (hoặc Thực đơn) theo khách hàng + ngày + bữa ăn (JSON). */
    public function sourceDishes(Request $request, FindSampleSourceDishesHandler $handler): JsonResponse
    {
        $this->authorize('create', FoodSampleLog::class);

        $params = $request->validate([
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'date'        => ['required', 'date'],
            'meal_time'   => ['required', 'in:breakfast,lunch,afternoon,dinner'],
        ]);

        $result = $handler->handle(new FindSampleSourceDishesQuery($params['customer_id'], $params['date'], $params['meal_time']));

        return response()->json(['found' => $result['found'], 'source' => $result['source'], 'data' => $result['rows']]);
    }

    /** Trang in Tem Lưu Mẫu (Mẫu số 4): mọi mẫu của phiếu, hoặc một mẫu nếu có ?detail={id}. Chỉ đọc. */
    public function labels(Request $request, FoodSampleLog $foodSample, GetFoodSampleLogHandler $handler)
    {
        $this->authorize('view', $foodSample);

        $log = $handler->handle(new GetFoodSampleLogQuery($foodSample));
        $samples = $request->filled('detail') ? $log->details->where('id', $request->query('detail')) : $log->details;
        abort_if($samples->isEmpty(), 404);

        return view('foodinspection::food-samples.labels', [
            'log'     => $log,
            'samples' => $samples->values(),
            'minHours' => FoodSampleDetail::MIN_RETENTION_HOURS,
        ]);
    }
}
