<?php

namespace Modules\GoodsReceipt\Support;

use Illuminate\Support\Carbon;
use Modules\GoodsReceipt\Exceptions\GoodsReceiptParseException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MisaGoodsReceiptParser
{
    private const COL_STT  = 'A';
    private const COL_NAME = 'D';
    private const COL_SKU  = 'K';
    private const COL_UNIT = 'L';
    private const COL_QTY  = 'N';

    public function parse(string $filePath): ParsedGoodsReceipt
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $misaRefId = $this->extractRefId($sheet);
        $supplierName = $this->extractSupplierName($sheet);
        $receiptDate = $this->extractReceiptDate($sheet);
        $items = $this->extractItems($sheet);

        if ($items === []) {
            throw new GoodsReceiptParseException('Không tìm thấy dòng hàng hóa nào trong file.');
        }

        return new ParsedGoodsReceipt($misaRefId, $supplierName, $receiptDate, $items);
    }

    private function extractRefId(Worksheet $sheet): string
    {
        $row = $this->findLabelValueRow($sheet, 'Số:');

        if ($row === null) {
            throw new GoodsReceiptParseException('Không tìm thấy ô "Số:" (số phiếu nhập kho) trong file.');
        }

        $raw = $this->cellText($sheet, $row, 'J');
        $refId = $this->sanitizeCode(preg_replace('/^\s*Số\s*:?/ui', '', $raw));

        if ($refId === '') {
            throw new GoodsReceiptParseException('Không đọc được số phiếu (ô "Số:") trong file.');
        }

        return $refId;
    }

    private function extractSupplierName(Worksheet $sheet): ?string
    {
        for ($row = 1; $row <= 15; $row++) {
            $label = $this->cellText($sheet, $row, 'A');
            if (str_contains($label, 'Họ và tên người giao')) {
                $name = trim($this->cellText($sheet, $row, 'F'));
                return $name !== '' ? $name : null;
            }
        }

        return null;
    }

    private function extractReceiptDate(Worksheet $sheet): ?Carbon
    {
        for ($row = 1; $row <= 15; $row++) {
            $text = $this->cellText($sheet, $row, 'J');
            if (preg_match('/Ngày\s*(\d{1,2})\s*tháng\s*(\d{1,2})\s*năm\s*(\d{4})/ui', $text, $m)) {
                return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
            }
        }

        return null;
    }

    private function extractItems(Worksheet $sheet): array
    {
        $startRow = $this->findTableStartRow($sheet);
        $highestRow = $sheet->getHighestDataRow();

        $items = [];

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $name = trim($this->cellText($sheet, $row, self::COL_NAME));
            $sku = $this->sanitizeCode($this->cellText($sheet, $row, self::COL_SKU));
            $stt = trim($this->cellText($sheet, $row, self::COL_STT));

            if (mb_strtolower($name) === 'cộng') {
                break;
            }

            if ($name === '' && $sku === '' && $stt === '') {
                continue;
            }

            if ($sku === '') {
                throw new GoodsReceiptParseException(
                    "Dòng \"$name\" (STT $stt) thiếu Mã số hàng hóa — không thể xác định sản phẩm."
                );
            }

            $unit = trim($this->cellText($sheet, $row, self::COL_UNIT));
            $qty = (float) $this->cellValue($sheet, $row, self::COL_QTY);

            $items[] = new ParsedGoodsReceiptItem(
                lineNo: $stt !== '' ? (int) $stt : null,
                name: $name,
                sku: $sku,
                unit: $unit !== '' ? $unit : null,
                quantity: $qty,
            );
        }

        return $items;
    }

    private function findTableStartRow(Worksheet $sheet): int
    {
        $highestRow = $sheet->getHighestDataRow();

        for ($row = 1; $row <= $highestRow; $row++) {
            if (trim($this->cellText($sheet, $row, self::COL_STT)) === 'A') {
                return $row + 1;
            }
        }

        throw new GoodsReceiptParseException('Không xác định được vị trí bảng chi tiết hàng hóa trong file.');
    }

    private function findLabelValueRow(Worksheet $sheet, string $needle): ?int
    {
        for ($row = 1; $row <= 15; $row++) {
            if (str_contains($this->cellText($sheet, $row, 'J'), $needle)) {
                return $row;
            }
        }

        return null;
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
        $cell = $sheet->getCell("{$column}{$row}");

        return $cell->getCalculatedValue();
    }
}
