<?php

namespace Modules\LabelTemplate\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\LabelTemplate\Models\LabelTemplate;

class ListLabelTemplateSizesHandler implements QueryHandlerInterface
{
    /** @return array<int, array{value: string, text: string}> */
    public function handle(QueryInterface $query): array
    {
        return LabelTemplate::query()
            ->whereNotNull('default_size')
            ->where('default_size', '!=', '')
            ->distinct()
            ->orderBy('default_size')
            ->pluck('default_size')
            ->map(fn (string $size) => ['value' => $size, 'text' => $size])
            ->all();
    }
}
