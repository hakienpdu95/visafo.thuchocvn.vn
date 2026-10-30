<?php

namespace Modules\SalesOrder\Support;

use Modules\SalesOrder\Exceptions\SalesOrderParseException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Đọc file Excel MISA Mẫu "02 - VT Phiếu xuất kho" bằng cách dò nhãn thay vì tọa độ cố định
 * (vì MISA có thể thêm/bớt dòng hoặc gộp ô): đầu phiếu theo nhãn, bảng hàng hóa theo tiêu đề cột.
 */
class MisaSalesOrderParser
{
    // Nhãn đầu phiếu, ưu tiên từ trái qua phải. Bắt buộc có ":" ngay sau nhãn để không nhầm
    // "Mẫu số: 02 - VT", "Số nhà 120...", "Số chứng từ gốc kèm theo:", "Địa điểm:".
    private const REF_ID_LABELS   = ['Số(?:\s+phiếu)?'];
    private const CUSTOMER_LABELS = ['Họ\s+(?:và\s+)?tên\s+người\s+nhận\s+hàng', 'Đơn\s+vị'];
    private const ADDRESS_LABELS  = ['Địa\s+chỉ\s*\(bộ\s+phận\)', 'Địa\s+chỉ'];

    // Số dòng tối đa dưới dòng tiêu đề chính còn thuộc phần tiêu đề bảng (tiêu đề phụ, ký hiệu cột).
    private const TABLE_HEADER_EXTRA_ROWS = 3;

    // Tiêu đề cột bảng hàng hóa => regex nhận diện (tiêu đề đã chuẩn hóa chữ thường).
    private const COLUMN_HEADERS = [
        'stt'  => '/^stt$/u',
        'name' => '/^tên\b/u',
        'sku'  => '/^mã số$/u',
        'unit' => '/^đơn vị tính$/u',
        'qty'  => '/yêu cầu/u', // bỏ qua cột "Thực xuất"
    ];

    private const COLUMN_LABELS = [
        'stt'  => 'STT',
        'name' => 'Tên hàng',
        'sku'  => 'Mã số',
        'unit' => 'Đơn vị tính',
        'qty'  => 'Yêu cầu',
    ];

    public function parse(string $filePath): ParsedSalesOrder
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $headerRow = $this->findTableHeaderRow($sheet);
        $items = $this->extractItems($sheet, $headerRow);

        if ($items === []) {
            throw new SalesOrderParseException('Không tìm thấy dòng hàng hóa nào trong file.');
        }

        return new ParsedSalesOrder(
            misaRefId: $this->extractRefId($sheet, $headerRow),
            customerName: $this->extractHeaderText($sheet, $headerRow, self::CUSTOMER_LABELS),
            deliveryAddress: $this->extractHeaderText($sheet, $headerRow, self::ADDRESS_LABELS),
            items: $items,
        );
    }

    /** Dòng tiêu đề chính của bảng hàng hóa = dòng đầu tiên có ô đúng bằng "STT" hoặc "Mã số". */
    private function findTableHeaderRow(Worksheet $sheet): int
    {
        $highestRow = $sheet->getHighestDataRow();

        for ($row = 1; $row <= $highestRow; $row++) {
            foreach ($sheet->getColumnIterator() as $column) {
                $header = $this->normalizeHeader($this->cellText($sheet, $row, $column->getColumnIndex()));

                if ($header === 'stt' || $header === 'mã số') {
                    return $row;
                }
            }
        }

        throw new SalesOrderParseException(
            'Không tìm thấy bảng hàng hóa (dòng tiêu đề "STT", "Mã số") trong file.'
        );
    }

    private function extractRefId(Worksheet $sheet, int $headerRow): string
    {
        foreach ($this->findLabelValues($sheet, $headerRow, self::REF_ID_LABELS[0]) as $value) {
            $refId = $this->sanitizeCode($value);

            // Có ":" nghĩa là ô bên cạnh là nhãn khác (VD "Có: 156"), không phải số phiếu.
            if ($refId !== '' && ! str_contains($refId, ':')) {
                return $refId;
            }
        }

        throw new SalesOrderParseException('Không tìm thấy Số phiếu. Vui lòng kiểm tra lại biểu mẫu MISA.');
    }

    /**
     * Lấy giá trị theo nhãn ưu tiên cao nhất có dữ liệu. Trong cùng một nhãn lấy dòng cuối cùng,
     * vì thông tin người nhận nằm sát bảng hàng, còn thông tin đơn vị xuất phiếu nằm trên cùng.
     *
     * @param  string[]  $labels
     */
    private function extractHeaderText(Worksheet $sheet, int $headerRow, array $labels): ?string
    {
        foreach ($labels as $label) {
            $values = array_filter($this->findLabelValues($sheet, $headerRow, $label), fn ($value) => $value !== '');

            if ($values !== []) {
                return end($values);
            }
        }

        return null;
    }

    /**
     * Giá trị của mọi ô khớp nhãn phía trên bảng hàng: phần sau dấu ":" trong cùng ô,
     * hoặc ô có dữ liệu kế tiếp trên dòng nếu nhãn và giá trị bị tách ô.
     *
     * @return string[]
     */
    private function findLabelValues(Worksheet $sheet, int $headerRow, string $label): array
    {
        $values = [];

        for ($row = 1; $row < $headerRow; $row++) {
            $cells = [];
            foreach ($sheet->getColumnIterator() as $column) {
                $text = $this->collapseSpaces($this->cellText($sheet, $row, $column->getColumnIndex()));

                if ($text !== '' && $text !== '-') {
                    $cells[] = $text;
                }
            }

            foreach ($cells as $index => $text) {
                if (! preg_match('/^[-–—]?\s*' . $label . '\s*:\s*(.*)$/ui', $text, $match)) {
                    continue;
                }

                $next = $cells[$index + 1] ?? '';
                $values[] = $match[1] !== '' ? $match[1] : (str_ends_with($next, ':') ? '' : $next);
            }
        }

        return $values;
    }

    /**
     * Gom nhóm các dòng trùng Mã số, cộng dồn SL yêu cầu. Dừng khi gặp "Cộng".
     *
     * @return ParsedSalesOrderItem[]
     */
    private function extractItems(Worksheet $sheet, int $headerRow): array
    {
        $columns = $this->detectColumns($sheet, $headerRow);
        $startRow = $this->findTableStartRow($sheet, $headerRow, $columns);
        $highestRow = $sheet->getHighestDataRow();

        /** @var array<string, array{lineNo: ?int, name: string, unit: ?string, qty: float}> $aggregated */
        $aggregated = [];

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $stt = trim($this->cellText($sheet, $row, $columns['stt']));
            $name = trim($this->cellText($sheet, $row, $columns['name']));
            $sku = $this->sanitizeCode($this->cellText($sheet, $row, $columns['sku']));

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

            $qty = $this->cellQty($sheet, $row, $columns['qty'], $name);

            if (isset($aggregated[$sku])) {
                $aggregated[$sku]['qty'] += $qty;

                continue;
            }

            $unit = trim($this->cellText($sheet, $row, $columns['unit']));
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

    /**
     * Tìm chữ cái cột theo tiêu đề. "Yêu cầu" nằm ở dòng tiêu đề phụ, dưới dòng chứa "Mã số".
     *
     * @return array{stt: string, name: string, sku: string, unit: string, qty: string}
     */
    private function detectColumns(Worksheet $sheet, int $headerRow): array
    {
        $columns = [];

        for ($row = $headerRow; $row <= $headerRow + self::TABLE_HEADER_EXTRA_ROWS; $row++) {
            foreach ($sheet->getColumnIterator() as $column) {
                $letter = $column->getColumnIndex();
                $header = $this->normalizeHeader($this->cellText($sheet, $row, $letter));

                foreach (self::COLUMN_HEADERS as $key => $pattern) {
                    if (! isset($columns[$key]) && preg_match($pattern, $header)) {
                        $columns[$key] = $letter;
                    }
                }
            }
        }

        $missing = array_diff_key(self::COLUMN_LABELS, $columns);

        if ($missing !== []) {
            throw new SalesOrderParseException(
                'Không tìm thấy cột "' . implode('", "', $missing) . '" trong tiêu đề bảng hàng hóa của file.'
            );
        }

        return $columns;
    }

    private function normalizeHeader(string $value): string
    {
        return mb_strtolower($this->collapseSpaces($value));
    }

    private function collapseSpaces(string $value): string
    {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $value));
    }

    /**
     * Dòng bắt đầu danh sách hàng = dòng ngay dưới dòng đánh ký hiệu cột: cột STT = 'A' và cột Tên hàng = 'B'.
     *
     * @param  array{stt: string, name: string, sku: string, unit: string, qty: string}  $columns
     */
    private function findTableStartRow(Worksheet $sheet, int $headerRow, array $columns): int
    {
        for ($row = $headerRow + 1; $row <= $headerRow + self::TABLE_HEADER_EXTRA_ROWS; $row++) {
            if (trim($this->cellText($sheet, $row, $columns['stt'])) === 'A'
                && trim($this->cellText($sheet, $row, $columns['name'])) === 'B') {
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

    private function cellQty(Worksheet $sheet, int $row, string $column, string $name): float
    {
        $value = $this->cellValue($sheet, $row, $column);

        if ($value === null || $value === '') {
            return 0.0;
        }

        if (! is_numeric($value)) {
            throw new SalesOrderParseException(
                "Dòng \"$name\": Số lượng yêu cầu \"$value\" (ô {$column}{$row}) không phải số."
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
