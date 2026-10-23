<?php

namespace App\Support;

use Illuminate\Support\Str;
use Modules\Product\Models\Product;

class ProductNameMismatch
{
    /**
     * @param  array<int, array{sku: string, name: string}>  $lines
     * @return string[]
     */
    public static function findInLines(array $lines): array
    {
        $catalog = Product::query()
            ->whereIn('sku', array_column($lines, 'sku'))
            ->pluck('name', 'sku')
            ->mapWithKeys(fn ($name, $sku) => [mb_strtolower($sku) => $name]);

        $found = [];

        foreach ($lines as $line) {
            $name = $catalog->get(mb_strtolower($line['sku']));

            if ($name !== null && self::isSuspicious($name, $line['name'])) {
                $found[$line['sku'] . '|' . $line['name']] = self::describe($line['sku'], $line['name'], $name);
            }
        }

        return array_values($found);
    }

    public static function isSuspicious(?string $catalogName, ?string $documentName): bool
    {
        $catalog = self::tokens($catalogName);
        $document = self::tokens($documentName);

        if ($catalog === [] || $document === []) {
            return false;
        }

        $catalogNumbers = self::numbers($catalog);
        $documentNumbers = self::numbers($document);

        if ($catalogNumbers !== [] && $documentNumbers !== [] && $catalogNumbers !== $documentNumbers) {
            return true;
        }

        $common = count(array_intersect($catalog, $document));

        return $common / min(count($catalog), count($document)) < 0.5;
    }

    /** @return string[] */
    private static function tokens(?string $name): array
    {
        $name = Str::ascii(mb_strtolower((string) $name));
        $name = (string) preg_replace('/\(.*?\)|\(.*$/', ' ', $name);

        return preg_split('/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @param  string[]  $tokens
     * @return string[]
     */
    private static function numbers(array $tokens): array
    {
        preg_match_all('/\d+/', implode(' ', $tokens), $m);

        return $m[0];
    }

    public static function describe(string $sku, string $documentName, string $catalogName): string
    {
        return "Mã $sku: phiếu ghi \"$documentName\" nhưng danh mục là \"$catalogName\"";
    }
}
