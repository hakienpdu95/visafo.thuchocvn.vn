<?php

namespace Modules\Warehouse\Support;

class TraceabilityLinkBuilder
{
    public function build(string $sku, string $batchCode, int $serial): string
    {
        return rtrim((string) config('app.url'), '/') . sprintf(
            '/qr/01/%s/10/%s/21/%06d',
            rawurlencode($sku),
            rawurlencode($batchCode),
            $serial,
        );
    }

    /** @return array{sku: string, batch_code: string, serial: int}|null */
    public function parse(string $value): ?array
    {
        if (! preg_match('#/01/([^/]+)/10/([^/]+)/21/(\d+)#', $value, $matches)) {
            return null;
        }

        return [
            'sku'        => rawurldecode($matches[1]),
            'batch_code' => rawurldecode($matches[2]),
            'serial'     => (int) $matches[3],
        ];
    }

    /** Kiến trúc Định danh Tách rời — URL cố định tại thời điểm in, độc lập với sản phẩm/lô. */
    public function buildFromUid(string $uid): string
    {
        return rtrim((string) config('app.url'), '/') . '/id/ser/' . rawurlencode($uid);
    }

    public function parseUid(string $value): ?string
    {
        if (! preg_match('~/id/ser/([^/?#]+)~', $value, $matches)) {
            return null;
        }

        return rawurldecode($matches[1]);
    }
}
