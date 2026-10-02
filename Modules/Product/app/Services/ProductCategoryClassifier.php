<?php

namespace Modules\Product\Services;

class ProductCategoryClassifier
{
    public const MEAT_SEAFOOD = 'fresh_meat_seafood';
    public const PRODUCE      = 'fresh_produce';
    public const PROCESSED    = 'processed_food';

    private const PROCESSED_PREFIXES = [
        'phở', 'bún', 'bánh', 'xôi', 'đậu phụ', 'đậu hũ', 'váng đậu', 'mề chay',
        'giò lụa', 'giò tai', 'giò bò', 'giò xào', 'giò sống', 'chả', 'mọc', 'nem', 'ruốc',
        'xúc xích', 'lạp xưởng', 'dăm bông', 'vịt quay', 'tai chua', 'trứng muối',
        'mẻ', 'dấm', 'kim chi', 'caramen', 'nước dừa', 'măng trúc gói',
    ];

    private const PROCESSED_KEYWORDS = [
        'hun khói', 'xông khói', 'muối', 'măng củ chua', 'măng lá', 'hà lan hộp',
    ];

    private const MEAT_SEAFOOD_KEYWORDS = [
        'thịt', 'gà', 'vịt', 'ngan', 'ngỗng', 'chim', 'bò', 'bê', 'trâu', 'lợn', 'heo',
        'sườn', 'sương hom', 'xương', 'nạc', 'ba chỉ', 'sấn', 'thăn', 'nạm', 'gầu', 'vai', 'đùi', 'cánh', 'ức', 'má',
        'chân giò', 'móng giò', 'bắp giò', 'bắp bò', 'bắp gân', 'giò lợn', 'tỏi gà', 'tỏi cộc', 'bầu dục',
        'tim', 'gan', 'lưỡi', 'dạ dầy', 'tai lợn', 'cật', 'mỡ', 'lòng', 'tiết', 'mề', 'khấu đuôi', 'mặt mũi', 'rẻ sườn',
        'trứng', 'cá', 'tôm', 'mực', 'cua', 'ngao', 'hàu', 'ốc', 'ếch', 'tép', 'sò', 'basa', 'diềm thăn',
    ];

    private const PRODUCE_KEYWORDS = [
        'rau', 'củ', 'quả', 'qủa', 'lá', 'hoa', 'nấm', 'ngọn', 'nụ', 'ngồng', 'ngó',
        'cải', 'bắp cải', 'bí', 'cà', 'hành', 'hanh tây', 'tỏi', 'ớt', 'khoai', 'dưa', 'ngô', 'đậu', 'đỗ', 'giá đỗ', 'măng',
        'su hào', 'su su', 'súp lơ', 'xà lách', 'mướp', 'mồng tơi', 'cần', 'ngải cứu', 'dọc mùng', 'đu đủ', 'tiêu xanh',
        'húng', 'mùi', 'thì là', 'tía tô', 'gừng', 'giềng', 'riềng',
        'chuối', 'cam', 'táo', 'nho', 'xoài', 'bưởi', 'dứa', 'dừa', 'chanh', 'quất', 'ổi', 'ôỉ', 'lê', 'mận', 'vải', 'kiwi',
        'thanh long', 'dâu', 'đào', 'bơ', 'roi', 'khế', 'quýt', 'cau',
    ];

    public function isProcessed(string $name): bool
    {
        $name = mb_strtolower(trim($name));

        foreach (self::PROCESSED_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        foreach (self::PROCESSED_KEYWORDS as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function suggest(string $name): ?string
    {
        if ($this->isProcessed($name)) {
            return self::PROCESSED;
        }

        $name = mb_strtolower(trim($name));
        $best = null;

        foreach ([self::MEAT_SEAFOOD => self::MEAT_SEAFOOD_KEYWORDS, self::PRODUCE => self::PRODUCE_KEYWORDS] as $group => $keywords) {
            foreach ($keywords as $keyword) {
                if (!preg_match('/(?<![\p{L}\p{N}])' . preg_quote($keyword, '/') . '(?![\p{L}\p{N}])/u', $name, $match, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                $position = $match[0][1];
                $length   = strlen($keyword);

                if ($best === null || $position < $best[1] || ($position === $best[1] && $length > $best[2])) {
                    $best = [$group, $position, $length];
                }
            }
        }

        return $best[0] ?? null;
    }
}
