<?php

namespace Modules\FoodInspection\Support;

/** Gợi ý dụng cụ lưu mẫu (có nắp đậy kín) cho ô "Dụng cụ" ở Sổ lưu mẫu; vẫn cho gõ tự do. */
class SampleContainerSuggestions
{
    /** @return array<int, string> */
    public static function all(): array
    {
        return ['Hộp inox có nắp', 'Hộp nhựa có nắp', 'Hộp thủy tinh có nắp', 'Túi zip thực phẩm', 'Hộp theo quy cách'];
    }
}
