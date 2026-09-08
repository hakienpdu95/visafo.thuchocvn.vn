<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Warehouse\Models\RetailItemTag;
use Modules\Warehouse\Models\TagRoll;
use Modules\Warehouse\Support\Gs1SerialGenerator;
use Modules\Warehouse\Support\TagRangeGrouper;

class GetOutboundOrderTagAllocationsHandler implements QueryHandlerInterface
{
    public function __construct(private readonly Gs1SerialGenerator $serialGenerator) {}

    /**
     * @return array<string, array{segments: array, assigned_count: int}> khoá theo OutboundPickedBatch->id
     */
    public function handle(QueryInterface $query): array
    {
        /** @var GetOutboundOrderTagAllocationsQuery $query */
        $order = $query->order;
        $rolls = TagRoll::all(['prefix', 'from_sequence', 'to_sequence']);

        $result = [];

        foreach ($order->pickedBatches as $line) {
            $tags = RetailItemTag::where('batch_id', $line->batch_id)
                ->where('outbound_order_id', $order->id)
                ->orderByRaw('visual_sequence IS NULL, visual_sequence ASC')
                ->get(['id', 'visual_sequence', 'status', 'updated_at']);

            $segments = array_map(
                fn (array $segment) => $segment + ['label' => $this->buildLabel($segment)],
                TagRangeGrouper::group($tags, $rolls),
            );

            $result[$line->id] = [
                'segments'       => $segments,
                'assigned_count' => $tags->count(),
            ];
        }

        return $result;
    }

    private function buildLabel(array $segment): ?string
    {
        if ($segment['from'] === null || $segment['prefix'] === null) {
            return null;
        }

        $from = $this->serialGenerator->buildGs1Serial($segment['prefix'], $segment['from']);
        $to   = $this->serialGenerator->buildGs1Serial($segment['prefix'], $segment['to']);

        return $from === $to ? $from : "{$from} - {$to}";
    }
}
