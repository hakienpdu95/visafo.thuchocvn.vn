<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\RetailItemTag;

class GetTagRollAllocationMapHandler implements QueryHandlerInterface
{
    /** @return array<int, array{from: int, to: int, count: int, status: \Modules\Warehouse\Enums\RetailItemTagStatus, batch: ?\Modules\Warehouse\Models\Batch, updated_at: \Illuminate\Support\Carbon}> */
    public function handle(QueryInterface $query): array
    {
        /** @var GetTagRollAllocationMapQuery $query */
        $roll = $query->roll;

        $tags = RetailItemTag::whereBetween('visual_sequence', [$roll->from_sequence, $roll->to_sequence])
            ->orderBy('visual_sequence')
            ->with(['batch' => fn ($q) => $q->with('product')])
            ->get(['id', 'visual_sequence', 'batch_id', 'status', 'updated_at']);

        return $this->groupIntoContiguousSegments($tags);
    }

    /** @param \Illuminate\Support\Collection<int, RetailItemTag> $tags */
    private function groupIntoContiguousSegments($tags): array
    {
        $segments = [];
        $current  = null;

        foreach ($tags as $tag) {
            $sameGroup = $current
                && $current['batch_id'] === $tag->batch_id
                && $current['status'] === $tag->status
                && $tag->visual_sequence === $current['to'] + 1;

            if ($sameGroup) {
                $current['to']++;
                $current['count']++;
                $current['updated_at'] = $tag->updated_at;
            } else {
                if ($current) {
                    $segments[] = $current;
                }

                $current = [
                    'from'       => $tag->visual_sequence,
                    'to'         => $tag->visual_sequence,
                    'count'      => 1,
                    'status'     => $tag->status,
                    'batch_id'   => $tag->batch_id,
                    'batch'      => $tag->batch,
                    'updated_at' => $tag->updated_at,
                ];
            }
        }

        if ($current) {
            $segments[] = $current;
        }

        return $segments;
    }
}
