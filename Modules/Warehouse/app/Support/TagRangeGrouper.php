<?php

namespace Modules\Warehouse\Support;

use Illuminate\Support\Collection;
use Modules\Warehouse\Models\TagRoll;

class TagRangeGrouper
{
    /**
     * Gom nhóm tem thành các dải liên tục (Gaps and Islands) theo visual_sequence + status.
     * Tem cũ (sinh trước kiến trúc Tem Tiền định danh, không có visual_sequence) được gom
     * riêng theo status, không có dải số.
     *
     * @param  Collection<int, \Modules\Warehouse\Models\RetailItemTag>  $tags  đã sắp theo
     *         visual_sequence IS NULL, visual_sequence ASC
     * @param  Collection<int, TagRoll>|null  $rolls
     * @return array<int, array{prefix: ?string, from: ?int, to: ?int, count: int, status: \Modules\Warehouse\Enums\RetailItemTagStatus, updated_at: \Illuminate\Support\Carbon}>
     */
    public static function group(Collection $tags, ?Collection $rolls = null): array
    {
        $rolls ??= TagRoll::all(['prefix', 'from_sequence', 'to_sequence']);

        $segments = [];
        $current  = null;

        foreach ($tags as $tag) {
            $key = $tag->visual_sequence !== null ? 'seq|' . $tag->status->value : 'legacy|' . $tag->status->value;

            $extendsSequenced = $current
                && $current['key'] === $key
                && $tag->visual_sequence !== null
                && $current['to'] === $tag->visual_sequence - 1;

            $extendsLegacy = $current
                && $current['key'] === $key
                && $tag->visual_sequence === null
                && $current['to'] === null;

            if ($extendsSequenced || $extendsLegacy) {
                if ($tag->visual_sequence !== null) {
                    $current['to'] = $tag->visual_sequence;
                }
                $current['count']++;
                $current['updated_at'] = $tag->updated_at;
            } else {
                if ($current) {
                    $segments[] = $current;
                }

                $current = [
                    'key'        => $key,
                    'from'       => $tag->visual_sequence,
                    'to'         => $tag->visual_sequence,
                    'count'      => 1,
                    'status'     => $tag->status,
                    'updated_at' => $tag->updated_at,
                ];
            }
        }

        if ($current) {
            $segments[] = $current;
        }

        return array_map(fn (array $segment) => self::attachPrefix($segment, $rolls), $segments);
    }

    /** @param Collection<int, TagRoll> $rolls */
    private static function attachPrefix(array $segment, Collection $rolls): array
    {
        $segment['prefix'] = null;

        if ($segment['from'] !== null) {
            $roll = $rolls->first(fn (TagRoll $r) => $r->from_sequence <= $segment['from'] && $r->to_sequence >= $segment['to']);
            $segment['prefix'] = $roll?->prefix;
        }

        unset($segment['key']);

        return $segment;
    }
}
