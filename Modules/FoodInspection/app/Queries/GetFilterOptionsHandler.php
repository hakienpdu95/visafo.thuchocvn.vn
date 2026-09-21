<?php

namespace Modules\FoodInspection\Queries;

use App\Models\User;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\FoodInspection\Enums\FoodGroup;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;

class GetFilterOptionsHandler implements QueryHandlerInterface
{
    /** @return array{inspectors: array<int, array{value: string, text: string}>, foodGroups: array<int, array{value: string, text: string}>, results: array<int, array{value: string, text: string}>} */
    public function handle(QueryInterface $query): array
    {
        $inspectorIds = FoodInspectionStep1Log::query()->whereNotNull('inspected_by')->distinct()->pluck('inspected_by');

        return [
            'inspectors' => User::query()->whereIn('id', $inspectorIds)->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $u) => ['value' => $u->id, 'text' => $u->name])->all(),
            'foodGroups' => collect(FoodGroup::cases())->map(fn (FoodGroup $g) => ['value' => $g->value, 'text' => $g->label()])->all(),
            'results'    => [
                ['value' => ListFoodInspectionStep1LogsHandler::RESULT_PASSED, 'text' => 'Đạt toàn bộ'],
                ['value' => ListFoodInspectionStep1LogsHandler::RESULT_FAILED, 'text' => 'Có hàng không đạt'],
            ],
        ];
    }
}
