<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductCategoryClassifier;

class SetProductCategoryCommand extends Command
{
    protected $signature = 'app:set-product-category
        {--auto : Tự xác định nhóm theo tên sản phẩm trong DB (thịt/thủy sản, rau củ quả, chế biến) — mặc định áp dụng cho nhóm fresh_food}
        {--to= : Mã nhóm hàng đích (categories.code); "none" = bỏ nhóm}
        {--from= : Chỉ lấy sản phẩm đang ở nhóm này; "none" = chưa có nhóm}
        {--sku=* : SKU cần đổi (lặp lại option hoặc ngăn cách bằng dấu phẩy)}
        {--name=* : Từ khóa trong tên sản phẩm (khớp một trong các từ khóa)}
        {--except=* : Từ khóa loại trừ trong tên sản phẩm}
        {--dry-run : Chỉ in danh sách thay đổi, không ghi DB}
        {--force : Bỏ qua bước xác nhận}';

    protected $description = 'Đổi nhóm hàng (category) của sản phẩm dựa trên dữ liệu bảng products — chỉ ghi cột category_id';

    private const NONE = 'none';

    private const DEFAULT_AUTO_FROM = 'fresh_food';

    public function handle(ProductCategoryClassifier $classifier): int
    {
        $auto = (bool) $this->option('auto');
        $to   = $this->option('to');
        $from = $this->option('from') ?? ($auto ? self::DEFAULT_AUTO_FROM : null);

        if ($auto && $to !== null) {
            $this->error('Chỉ dùng một trong hai: --auto hoặc --to.');
            return self::FAILURE;
        }

        if (!$auto && ($to === null || $to === '')) {
            $this->error('Thiếu --to (mã nhóm hàng đích) hoặc --auto.');
            return self::FAILURE;
        }

        $skus  = $this->listOption('sku');
        $names = $this->listOption('name');

        if ($from === null && $skus === [] && $names === []) {
            $this->error('Cần ít nhất một bộ lọc: --from, --sku hoặc --name.');
            return self::FAILURE;
        }

        $categoryIds = Category::query()->pluck('id', 'code');
        $codeById    = Category::withTrashed()->pluck('code', 'id');

        $required = $auto
            ? [ProductCategoryClassifier::MEAT_SEAFOOD, ProductCategoryClassifier::PRODUCE, ProductCategoryClassifier::PROCESSED, $from]
            : [$to, $from];
        $unknown = array_filter(
            array_unique($required),
            fn ($code) => $code !== null && $code !== self::NONE && !$categoryIds->has($code),
        );
        if ($unknown !== []) {
            $this->error('Mã nhóm hàng không tồn tại: ' . implode(', ', $unknown));
            $this->line('Các mã hợp lệ: ' . $categoryIds->keys()->implode(', ') . ', ' . self::NONE);
            return self::FAILURE;
        }

        $products = $this->filteredQuery($categoryIds, $from, $skus, $names)->get();

        $notFound = array_diff($skus, $products->pluck('sku')->all());
        if ($notFound !== []) {
            $this->warn('SKU không khớp bộ lọc / không có trong DB: ' . implode(', ', $notFound));
        }

        $changes    = [];
        $undecided  = [];
        $same       = 0;
        $fixedTarget = $to === self::NONE ? null : $to;

        foreach ($products as $product) {
            $current = $product->category_id !== null ? ($codeById[$product->category_id] ?? '?') : null;
            $target  = $auto ? $classifier->suggest($product->name) : $fixedTarget;

            if ($auto && $target === null) {
                $undecided[] = [$product->sku, $product->name, $current ?? '—'];
                continue;
            }

            if ($current === $target) {
                $same++;
                continue;
            }

            $changes[] = ['product' => $product, 'from' => $current, 'to' => $target];
        }

        if ($changes !== []) {
            $this->table(
                ['SKU', 'Tên', 'Nhóm hiện tại', 'Nhóm mới'],
                array_map(fn ($c) => [$c['product']->sku, $c['product']->name, $c['from'] ?? '—', $c['to'] ?? '—'], $changes),
            );
        }

        if ($undecided !== []) {
            $this->warn('Không xác định được nhóm theo tên — giữ nguyên, cần xử lý bằng --to:');
            $this->table(['SKU', 'Tên', 'Nhóm hiện tại'], $undecided);
        }

        if ($changes !== []) {
            $this->line('Tổng hợp thay đổi:');
            foreach (collect($changes)->countBy(fn ($c) => ($c['from'] ?? '—') . ' => ' . ($c['to'] ?? '—')) as $move => $count) {
                $this->line("  - $move: $count");
            }
        }

        $this->line('Sẽ đổi nhóm: ' . count($changes));
        $this->line("Giữ nguyên (đã đúng nhóm): $same");
        if ($auto) {
            $this->line('Giữ nguyên (không xác định được): ' . count($undecided));
        }

        if ($changes === []) {
            $this->info('Không có gì để cập nhật.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('dry-run — chưa ghi DB.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Ghi ' . count($changes) . ' thay đổi nhóm hàng vào DB?')) {
            $this->warn('Đã hủy — chưa ghi DB.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes, $categoryIds): void {
            foreach ($changes as $change) {
                $change['product']->update([
                    'category_id' => $change['to'] !== null ? $categoryIds->get($change['to']) : null,
                ]);
            }
        });

        $this->info('Đã cập nhật nhóm hàng cho ' . count($changes) . ' sản phẩm.');

        return self::SUCCESS;
    }

    private function filteredQuery(Collection $categoryIds, ?string $from, array $skus, array $names): Builder
    {
        $query = Product::query()->orderBy('name');

        if ($from === self::NONE) {
            $query->whereNull('category_id');
        } elseif ($from !== null) {
            $query->where('category_id', $categoryIds->get($from));
        }

        if ($skus !== []) {
            $query->whereIn('sku', $skus);
        }

        if ($names !== []) {
            $query->where(function ($q) use ($names): void {
                foreach ($names as $keyword) {
                    $q->orWhere('name', 'like', '%' . addcslashes($keyword, '%_\\') . '%');
                }
            });
        }

        foreach ($this->listOption('except') as $keyword) {
            $query->where('name', 'not like', '%' . addcslashes($keyword, '%_\\') . '%');
        }

        return $query;
    }

    private function listOption(string $name): array
    {
        return collect($this->option($name))
            ->flatMap(fn ($value) => explode(',', (string) $value))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }
}
