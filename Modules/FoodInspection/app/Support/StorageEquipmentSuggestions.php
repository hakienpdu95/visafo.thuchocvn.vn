<?php

namespace Modules\FoodInspection\Support;

/** Gợi ý dụng cụ chứa đựng / bảo quản thường dùng cho ô "Dụng cụ" ở Sổ Bước 3 (vẫn cho gõ tự do). */
class StorageEquipmentSuggestions
{
    /** @return array<int, string> */
    public static function all(): array
    {
        return [
            'Khay inox có nắp',
            'Hộp theo quy cách',
            'Thùng giữ nhiệt',
            'Hộp nhựa PET',
            'Nồi giữ nhiệt',
            'Khay inox bọc màng bọc thực phẩm',
            'Thùng nhựa có nắp đậy',
            'Tủ giữ nóng',
        ];
    }
}
