<?php

namespace Modules\Compliance\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Compliance\Enums\CompanyType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Hồ sơ pháp nhân ở Trụ sở chính (form "Cập nhật thông tin doanh nghiệp" trên trang Hồ sơ năng lực). */
class CompanyProfileData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $company_name,

        #[Required]
        public readonly CompanyType $company_type,

        // MST 10 số (chi nhánh: 10 số + "-" + 3 số) hoặc CCCD 12 số (hộ kinh doanh)
        #[Required, StringType, Regex('/^(\d{10}(-\d{3})?|\d{12})$/')]
        public readonly string $tax_code,

        public readonly ?string $tax_code_issue_date,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $tax_code_issue_place,

        public readonly string $province_code,

        public readonly string $ward_code,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address,

        // HTML từ Jodit (đã có ảnh nhúng dạng URL, không base64) — làm sạch ở UpdateCompanyProfileAction
        #[Nullable, StringType, Max(100000)]
        public readonly ?string $supply_chain_role = null,
    ) {}

    /** Lưu ý: rules() THAY THẾ rule từ attribute của các field khai báo ở đây — nên khai báo đủ. */
    public static function rules(ValidationContext $context): array
    {
        return [
            'tax_code_issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'province_code'       => ['required', 'string', Rule::exists('provinces', 'province_code')],
            // Phường/Xã phải thuộc đúng Tỉnh/TP đã chọn (địa giới 2 cấp, không còn quận/huyện)
            'ward_code'           => ['required', 'string', Rule::exists('wards', 'ward_code')->where('province_code', $context->payload['province_code'] ?? null)],
        ];
    }

    public static function messages(): array
    {
        return [
            'company_name.required'               => 'Vui lòng nhập tên doanh nghiệp / hộ kinh doanh.',
            'company_name.max'                    => 'Tên doanh nghiệp không được vượt quá 255 ký tự.',
            'company_type.required'               => 'Vui lòng chọn loại hình tổ chức.',
            'company_type.enum'                   => 'Loại hình tổ chức không hợp lệ.',
            'tax_code.required'                   => 'Vui lòng nhập mã số định danh (MST/CCCD).',
            'tax_code.regex'                      => 'Mã số định danh phải là MST 10 số (hoặc 10 số-3 số) hoặc CCCD 12 số.',
            'tax_code_issue_date.date'            => 'Ngày cấp không hợp lệ.',
            'tax_code_issue_date.before_or_equal' => 'Ngày cấp không được sau ngày hôm nay.',
            'tax_code_issue_place.max'            => 'Nơi cấp không được vượt quá 255 ký tự.',
            'province_code.required'              => 'Vui lòng chọn tỉnh / thành phố.',
            'province_code.exists'                => 'Tỉnh / thành phố không hợp lệ.',
            'ward_code.required'                  => 'Vui lòng chọn phường / xã.',
            'ward_code.exists'                    => 'Phường / xã không thuộc tỉnh / thành phố đã chọn.',
            'address.max'                         => 'Địa chỉ không được vượt quá 500 ký tự.',
            'supply_chain_role.max'               => 'Nội dung "Vai trò trong chuỗi cung ứng" quá dài.',
        ];
    }
}
