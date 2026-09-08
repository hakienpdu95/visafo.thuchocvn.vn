<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Support\TagRangeGrouper;

class GetBatchTagAllocationsHandler implements QueryHandlerInterface
{
    /**
     * @return array<int, array{prefix: ?string, from: ?int, to: ?int, count: int, status: \Modules\Warehouse\Enums\RetailItemTagStatus, updated_at: \Illuminate\Support\Carbon}>
     */
    public function handle(QueryInterface $query): array
    {
        /** @var GetBatchTagAllocationsQuery $query */
        $batch = $query->batch;

        $tags = RetailItemTag::where('batch_id', $batch->id)
            ->orderByRaw('visual_sequence IS NULL, visual_sequence ASC')
            ->get(['id', 'visual_sequence', 'status', 'updated_at']);

        return TagRangeGrouper::group($tags);
    }
}
