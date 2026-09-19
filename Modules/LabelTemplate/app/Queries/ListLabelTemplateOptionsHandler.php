<?php

namespace Modules\LabelTemplate\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\LabelTemplate\Models\LabelTemplate;

class ListLabelTemplateOptionsHandler implements QueryHandlerInterface
{
    /** @return array<int, array{value: string, text: string}> */
    public function handle(QueryInterface $query): array
    {
        return LabelTemplate::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (LabelTemplate $t) => ['value' => $t->id, 'text' => $t->name])
            ->all();
    }
}
