<?php

namespace Modules\Product\Enums;

enum ProductCategoryType: string
{
    case Food               = 'food';
    case Cosmetic           = 'cosmetic';
    case MedicalDevice      = 'medical_device';
    case ToyPlastic         = 'toy_plastic';
    case Textile            = 'textile';
    case ConsumerGoods      = 'consumer_goods';
    case FoodContactMaterial = 'food_contact_material';
    case ElectricalAppliance = 'electrical_appliance';

    public function label(): string
    {
        return match ($this) {
            self::Food               => 'Thực phẩm / Sữa',
            self::Cosmetic           => 'Mỹ phẩm',
            self::MedicalDevice      => 'Thiết bị y tế',
            self::ToyPlastic         => 'Đồ chơi / Đồ nhựa',
            self::Textile            => 'Quần áo / Dệt may',
            self::ConsumerGoods      => 'Hóa phẩm & Đồ dùng gia đình',
            self::FoodContactMaterial => 'Dụng cụ tiếp xúc thực phẩm',
            self::ElectricalAppliance => 'Thiết bị điện gia dụng',
        };
    }
}
