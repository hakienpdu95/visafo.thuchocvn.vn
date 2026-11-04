<?php

namespace Modules\HanoiCheck\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\HanoiCheck\Models\HanoiCheckSyncLog;

class GetHanoiCheckSyncStateHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        $base = HanoiCheckSyncLog::query()
            ->where('entity_type', $query->entity->value)
            ->where('local_id', $query->localId)
            ->orderByDesc('created_at')->orderByDesc('id');

        if ($query->endpoint !== null) {
            $last = (clone $base)->where('endpoint', $query->endpoint)->first();

            return [
                'last'   => $last,
                'synced' => $last !== null && $last->status->synced() && $last->environment === $query->environment,
            ];
        }

        $latestPerEndpoint = (clone $base)
            ->where('environment', $query->environment)
            ->whereNotNull('endpoint')
            ->get(['endpoint', 'status'])
            ->unique('endpoint');

        return [
            'last'   => (clone $base)->first(),
            'synced' => $latestPerEndpoint->isNotEmpty() && $latestPerEndpoint->every(fn (HanoiCheckSyncLog $log) => $log->status->synced()),
        ];
    }
}
