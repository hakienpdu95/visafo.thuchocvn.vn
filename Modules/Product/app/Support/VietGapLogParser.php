<?php

namespace Modules\Product\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Product\Models\FarmingLog;
use OpenSpout\Reader\XLSX\Options;
use Rap2hpoutre\FastExcel\FastExcel;

class VietGapLogParser
{
    private const DEFAULT_COLUMNS = [
        'date'         => 0,
        'content'      => 1,
        'method'       => 2,
        'material'     => 3,
        'dosage'       => 4,
        'isolation'    => 5,
        'operator'     => 6,
        'harvest_date' => 7,
        'yield'        => 8,
        'harvester'    => 9,
        'buyer'        => 10,
    ];

    private const HEADER_KEYWORDS = [
        'date'         => 'thoi gian thuc hien',
        'content'      => 'noi dung',
        'method'       => 'phuong phap',
        'material'     => 'vat tu',
        'dosage'       => 'lieu luong',
        'isolation'    => 'cach ly',
        'operator'     => 'nguoi thuc hien',
        'harvest_date' => 'ngay thang',
        'yield'        => 'san luong',
        'harvester'    => 'nguoi thu hoach',
        'buyer'        => 'co so tieu thu',
    ];

    private const CATEGORY_KEYWORDS = [
        'harvest'    => ['thu hoach'],
        'pesticide'  => ['phong tru', 'phun', 'thuoc', 'tru sau', 'sau benh', 'benh'],
        'fertilizer' => ['bon', 'phan'],
        'water'      => ['tuoi', 'bom nuoc'],
        'planting'   => ['gieo', 'trong', 'cay giong', 'xuong giong'],
        'land_prep'  => ['lam dat', 'cay dat', 'bua', 'len luong', 'cat goc', 'lam co', 'nhat co', 've sinh dong ruong'],
    ];

    public function sheetSummaries(string $path): array
    {
        return collect($this->readSheets($path))
            ->map(fn (array $sheet, string $name) => [
                'name'        => $name,
                'sowing_date' => $this->headerDate($sheet, 'thoi gian gieo trong')?->toDateString(),
                'owner'       => $this->headerValue($sheet, 'chu ho'),
                'crop'        => $this->headerValue($sheet, 'giong cay trong'),
            ])
            ->values()
            ->all();
    }

    public function parse(string $path, string $sheetName, ?Carbon $sowingDate = null): array
    {
        $sheet = $this->readSheets($path)[$sheetName] ?? null;
        if ($sheet === null) {
            return ['rows' => [], 'errors' => ['Không tìm thấy sheet "' . $sheetName . '".'], 'warnings' => []];
        }

        $headerRow = $this->findHeaderRow($sheet);
        $cols = $this->detectColumns($sheet, $headerRow);
        $owner = $this->headerValue($sheet, 'chu ho');

        $rows = [];
        $errors = [];
        $warnings = [];
        $event = null;

        for ($r = $headerRow + 1, $last = count($sheet); $r <= $last; $r++) {
            $get = fn (string $key) => $this->text($sheet, $r, $cols[$key]);

            if (str_contains($this->normalize($get('content') . ' ' . $get('method')), 'nguoi giam sat')) {
                break;
            }

            $content = $get('content');
            $material = $get('material');

            if ($content !== '') {
                $date = $this->parseDate($get('date'));
                if ($date === null) {
                    $errors[] = 'Dòng ' . $r . ' ("' . Str::limit($content, 40) . '"): thiếu hoặc sai định dạng thời gian thực hiện.';
                    $event = null;
                } else {
                    $event = [
                        'row'      => $r,
                        'date'     => $date,
                        'content'  => $content,
                        'category' => $this->categorize($content),
                        'method'   => $get('method'),
                        'operator' => $get('operator') ?: $owner,
                    ];

                    if ($event['category'] === 'harvest') {
                        $rows[] = $this->harvestRow($r, $date, $event, $get('yield'), $get('buyer'), $get('harvester') ?: $event['operator']);
                    } else {
                        $rows[] = $this->careRow($r, $event, $material, $get('dosage'), $get('isolation'), $get('operator'));
                    }
                }
            } elseif ($material !== '') {
                if ($event === null) {
                    $errors[] = 'Dòng ' . $r . ' (vật tư "' . $material . '"): không xác định được nội dung/thời gian thực hiện.';
                } elseif ($event['category'] !== 'harvest') {
                    $rows[] = $this->careRow($r, $event, $material, $get('dosage'), $get('isolation'), $get('operator'));
                }
            }

            $harvestDate = $this->parseDate($get('harvest_date'));
            $isHarvestEventRow = $content !== '' && ($event['category'] ?? null) === 'harvest';
            if ($harvestDate !== null && !$isHarvestEventRow) {
                if ($sowingDate !== null && $harvestDate->lt($sowingDate->copy()->startOfDay())) {
                    $warnings[] = 'Dòng ' . $r . ': bỏ qua thu hoạch ngày ' . $harvestDate->format('d/m/Y') . ' vì trước ngày gieo của lô (thuộc vụ trước).';
                } else {
                    $rows[] = $this->harvestRow($r, $harvestDate, ['content' => 'Thu hoạch', 'method' => '', 'operator' => $owner], $get('yield'), $get('buyer'), $get('harvester') ?: $owner);
                }
            }
        }

        return ['rows' => $rows, 'errors' => $errors, 'warnings' => $warnings];
    }

    private function careRow(int $r, array $event, string $material, string $dosage, string $isolation, string $operator): array
    {
        $usesMaterial = in_array($event['category'], FarmingLog::MATERIAL_TYPES, true);
        $notes = $this->notes($event);
        if (!$usesMaterial && $material !== '') {
            $notes = trim('Vật tư: ' . $material . ($dosage !== '' ? ' (' . $dosage . ')' : '') . '. ' . $notes);
        }

        return [
            'row'            => $r,
            'activity_type'  => $event['category'],
            'activity_date'  => $event['date']->format('Y-m-d H:i:s'),
            'material_name'  => $usesMaterial && $material !== '' ? Str::limit($material, 250, '') : null,
            'dosage'         => $usesMaterial && $dosage !== '' ? Str::limit($dosage, 100, '') : null,
            'isolation_days' => $usesMaterial ? $this->parseInt($isolation) : null,
            'operator_name'  => Str::limit($operator ?: $event['operator'], 150, '') ?: null,
            'notes'          => Str::limit($notes, 500, '') ?: null,
        ];
    }

    private function harvestRow(int $r, Carbon $date, array $event, string $yield, string $buyer, string $operator): array
    {
        return [
            'row'           => $r,
            'activity_type' => 'harvest',
            'activity_date' => $date->format('Y-m-d H:i:s'),
            'harvest_yield' => $yield !== '' ? Str::limit($yield, 100, '') : null,
            'buyer_name'    => $buyer !== '' ? Str::limit($buyer, 255, '') : null,
            'operator_name' => Str::limit($operator, 150, '') ?: null,
            'notes'         => Str::limit($this->notes($event), 500, '') ?: null,
        ];
    }

    private function notes(array $event): string
    {
        $label = FarmingLog::ACTIVITY_TYPES[$event['category'] ?? 'harvest']['label'] ?? '';
        $method = trim($event['method']);

        if ($this->normalize($event['content']) === $this->normalize($label)) {
            return $method;
        }

        return trim($event['content'] . ($method !== '' ? ': ' . $method : ''));
    }

    private function categorize(string $content): string
    {
        $text = $this->normalize($content);
        $best = 'other';
        $bestPos = PHP_INT_MAX;

        foreach (self::CATEGORY_KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $text, $m, PREG_OFFSET_CAPTURE) && $m[0][1] < $bestPos) {
                    $best = $category;
                    $bestPos = $m[0][1];
                }
            }
        }

        return $best;
    }

    private function readSheets(string $path): array
    {
        return (new FastExcel())
            ->withoutHeaders()
            ->withSheetsNames()
            ->configureOptionsUsing(function ($options) {
                if ($options instanceof Options) {
                    $options->SHOULD_FORMAT_DATES = true;
                    $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
                }
            })
            ->importSheets($path)
            ->all();
    }

    private function findHeaderRow(array $sheet): int
    {
        for ($r = 1; $r <= 30; $r++) {
            foreach (array_keys($sheet[$r - 1] ?? []) as $col) {
                if (str_contains($this->normalize($this->text($sheet, $r, $col)), 'noi dung')) {
                    return $r;
                }
            }
        }

        return 8;
    }

    private function detectColumns(array $sheet, int $headerRow): array
    {
        $found = [];

        foreach ([$headerRow, $headerRow - 1] as $row) {
            foreach (array_keys($sheet[$row - 1] ?? []) as $col) {
                if (in_array($col, $found, true)) {
                    continue;
                }
                $text = $this->normalize($this->text($sheet, $row, $col));
                foreach (self::HEADER_KEYWORDS as $key => $keyword) {
                    if (!isset($found[$key]) && str_contains($text, $keyword)) {
                        $found[$key] = $col;
                        break;
                    }
                }
            }
        }

        return array_replace(self::DEFAULT_COLUMNS, $found);
    }

    private function headerValue(array $sheet, string $keyword): ?string
    {
        for ($r = 1; $r <= 10; $r++) {
            $raw = $this->text($sheet, $r, 0);
            if (str_contains($this->normalize($raw), $keyword) && str_contains($raw, ':')) {
                $value = trim(Str::after($raw, ':'));
                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function headerDate(array $sheet, string $keyword): ?Carbon
    {
        $value = $this->headerValue($sheet, $keyword);

        return $value ? $this->parseDate($value) : null;
    }

    private function parseDate(string $value): ?Carbon
    {
        if (!preg_match('/(\d{1,2})\s*[\/\-.]\s*(\d{1,2})\s*[\/\-.]\s*(\d{2,4})/', $value, $m)) {
            return null;
        }

        $year = (int) $m[3] < 100 ? 2000 + (int) $m[3] : (int) $m[3];

        return checkdate((int) $m[2], (int) $m[1], $year)
            ? Carbon::createFromDate($year, (int) $m[2], (int) $m[1])->startOfDay()
            : null;
    }

    private function parseInt(string $value): ?int
    {
        return preg_match('/\d+/', $value, $m) ? min((int) $m[0], 365) : null;
    }

    private function text(array $sheet, int $row, int $col): string
    {
        $value = $sheet[$row - 1][$col] ?? '';

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii($value))));
    }
}
