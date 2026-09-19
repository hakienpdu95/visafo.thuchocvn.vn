<?php

namespace Modules\SalesOrder\Support;

use Modules\SalesOrder\Exceptions\SalesOrderParseException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Đọc file Excel MISA Mẫu "02 - VT Phiếu xuất kho" theo tọa độ cố định
 * (phần đầu phiếu) + dò dòng bắt đầu bảng hàng hóa (vì MISA không cố định dòng này).
 */
class MisaSalesOrderParser
{
    // Phần đầu phiếu — dòng Excel 1-based (index 0-based ghi trong ngoặc).
    private const ROW_REF_ID   = 8;   // index 7
    private const COL_REF_ID   = 'I'; // index 8
    private const ROW_CUSTOMER = 9;   // index 8
    private const ROW_ADDRESS  = 10;  // index 9
    private const COL_HEADER   = 'A'; // index 0

    // Dải dò dòng "A | B" — index 13..20.
    private const SCAN_FROM_ROW = 14;
    private const SCAN_TO_ROW   = 21;

    // Bảng hàng hóa.
    private const COL_STT  = 'A'; // index 0
    private const COL_NAME = 'D'; // index 3
    private const COL_SKU  = 'J'; // index 9
    private const COL_UNIT = 'L'; // index 11
    private const COL_QTY  = 'M'; // index 12 — "Yêu cầu" (bỏ qua cột "Thực xuất")

    public function parse(string $filePath): ParsedSalesOrder
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $items = $this->extractItems($sheet);

        if ($items === []) {
            throw new SalesOrderParseException('Không tìm thấy dòng hàng hóa nào trong file.');
        }

        return new ParsedSalesOrder(
            misaRefId: $this->extractRefId($sheet),
            customerName: $this->extractHeaderText($sheet, self::ROW_CUSTOMER, 'Họ\s*tên\s*người\s*nhận\s*hàng'),
            deliveryAddress: $this->extractHeaderText($sheet, self::ROW_ADDRESS, 'Địa\s*chỉ\s*\(bộ\s*phận\)'),
            items: $items,
        );
    }

    private function extractRefId(Worksheet $sheet): string
    {
        $raw = $this->cellText($sheet, self::ROW_REF_ID, self::COL_REF_ID);
        $refId = $this->sanitizeCode(preg_replace('/^\s*Số\s*:?/ui', '', $raw));

        if ($refId === '') {
            throw new SalesOrderParseException(
                'Không đọc được số phiếu xuất kho (ô ' . self::COL_REF_ID . self::ROW_REF_ID . ') trong file.'
            );
        }

        return $refId;
    }

    private function extractHeaderText(Worksheet $sheet, int $row, string $labelPattern): ?string
    {
        $raw = $this->cellText($sheet, $row, self::COL_HEADER);
        $value = trim(preg_replace('/^\s*-?\s*' . $labelPattern . '\s*:?/ui', '', $raw));

        return $value !== '' ? $value : null;
    }

    /**
     * Gom nhóm các dòng trùng Mã số, cộng dồn SL yêu cầu. Dừng khi gặp "Cộng".
     *
     * @return ParsedSalesOrderItem[]
     */
    private function extractItems(Worksheet $sheet): array
    {
        $startRow = $this->findTableStartRow($sheet);
        $highestRow = $sheet->getHighestDataRow();

        /** @var array<string, array{lineNo: ?int, name: string, unit: ?string, qty: float}> $aggregated */
        $aggregated = [];

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $stt = trim($this->cellText($sheet, $row, self::COL_STT));
            $name = trim($this->cellText($sheet, $row, self::COL_NAME));
            $sku = $this->sanitizeCode($this->cellText($sheet, $row, self::COL_SKU));

            if ($this->isTotalRow($stt) || $this->isTotalRow($name)) {
                break;
            }

            if ($stt === '' && $name === '' && $sku === '') {
                continue;
            }

            if ($sku === '') {
                throw new SalesOrderParseException(
                    "Dòng \"$name\" (STT $stt) thiếu Mã số hàng hóa — không thể xác định sản phẩm."
                );
            }

            $qty = $this->cellQty($sheet, $row, $name);

            if (isset($aggregated[$sku])) {
                $aggregated[$sku]['qty'] += $qty;

                continue;
            }

            $unit = trim($this->cellText($sheet, $row, self::COL_UNIT));
            $aggregated[$sku] = [
                'lineNo' => $stt !== '' ? (int) $stt : null,
                'name'   => $name,
                'unit'   => $unit !== '' ? $unit : null,
                'qty'    => $qty,
            ];
        }

        $items = [];
        foreach ($aggregated as $sku => $row) {
            $items[] = new ParsedSalesOrderItem($row['lineNo'], $row['name'], (string) $sku, $row['unit'], round($row['qty'], 3));
        }

        return $items;
    }

    /** Dòng bắt đầu danh sách hàng = dòng ngay dưới dòng có Cột A = 'A' và Cột D = 'B'. */
    private function findTableStartRow(Worksheet $sheet): int
    {
        for ($row = self::SCAN_FROM_ROW; $row <= self::SCAN_TO_ROW; $row++) {
            if (trim($this->cellText($sheet, $row, self::COL_STT)) === 'A'
                && trim($this->cellText($sheet, $row, self::COL_NAME)) === 'B') {
                return $row + 1;
            }
        }

        throw new SalesOrderParseException(
            'Không xác định được vị trí bảng chi tiết hàng hóa (dòng tiêu đề cột "A"/"B") trong file.'
        );
    }

    private function isTotalRow(string $value): bool
    {
        return mb_strtolower($value) === 'cộng';
    }

    private function cellQty(Worksheet $sheet, int $row, string $name): float
    {
        $value = $this->cellValue($sheet, $row, self::COL_QTY);

        if ($value === null || $value === '') {
            return 0.0;
        }

        if (! is_numeric($value)) {
            throw new SalesOrderParseException(
                "Dòng \"$name\": Số lượng yêu cầu \"$value\" (ô " . self::COL_QTY . $row . ') không phải số.'
            );
        }

        return (float) $value;
    }

    private function sanitizeCode(string $value): string
    {
        $value = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', '', $value);

        return trim(preg_replace('/\s+/u', '', $value));
    }

    private function cellText(Worksheet $sheet, int $row, string $column): string
    {
        $value = $this->cellValue($sheet, $row, $column);

        return $value === null ? '' : (string) $value;
    }

    private function cellValue(Worksheet $sheet, int $row, string $column): string|int|float|null
    {
        return $sheet->getCell("{$column}{$row}")->getCalculatedValue();
    }
}
