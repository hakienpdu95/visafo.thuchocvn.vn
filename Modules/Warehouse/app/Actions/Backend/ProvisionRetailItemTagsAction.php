<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\TagRoll;
use Modules\Warehouse\Support\Gs1SerialGenerator;
use Modules\Warehouse\Support\TraceabilityLinkBuilder;

class ProvisionRetailItemTagsAction
{
    use AsAction;

    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly Gs1SerialGenerator $serialGenerator,
        private readonly TraceabilityLinkBuilder $linkBuilder,
    ) {}

    /** @return array{from: int, to: int, count: int, prefix: string, roll_id: string} */
    public function handle(int $count, string $prefix = '', ?string $createdBy = null): array
    {
        $nextSequence = ((int) DB::table('retail_item_tags')->max('visual_sequence')) + 1;
        $now          = now();
        $fromSequence = $nextSequence;
        $toSequence   = $nextSequence + $count - 1;

        $remaining = $count;
        $sequence  = $fromSequence;

        while ($remaining > 0) {
            $chunkSize = min(self::CHUNK_SIZE, $remaining);
            $uids      = $this->serialGenerator->generateUids($chunkSize);
            $rows      = [];

            foreach ($uids as $uid) {
                $rows[] = [
                    'id'              => (string) Str::ulid(),
                    'uid'             => $uid,
                    'gs1_serial'      => $this->serialGenerator->buildGs1Serial($prefix, $sequence),
                    'visual_sequence' => $sequence,
                    'qr_code'         => $this->linkBuilder->buildFromUid($uid),
                    'status'          => RetailItemTagStatus::Provisioned->value,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
                $sequence++;
            }

            DB::table('retail_item_tags')->insert($rows);
            $remaining -= $chunkSize;
        }

        $roll = TagRoll::create([
            'prefix'          => strtoupper($prefix),
            'from_sequence'   => $fromSequence,
            'to_sequence'     => $toSequence,
            'count'           => $count,
            'created_by'      => $createdBy ?? auth()->id(),
            'created_at'      => $now,
        ]);

        return ['from' => $fromSequence, 'to' => $toSequence, 'count' => $count, 'prefix' => strtoupper($prefix), 'roll_id' => $roll->id];
    }
}
