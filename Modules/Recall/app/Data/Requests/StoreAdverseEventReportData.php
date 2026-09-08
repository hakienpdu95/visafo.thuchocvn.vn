<?php

namespace Modules\Recall\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\Recall\Enums\AdverseEventOutcome;
use Modules\Recall\Enums\AdverseEventReportSource;
use Modules\Recall\Enums\ConsumerGender;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreAdverseEventReportData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $product_id,

        #[Nullable]
        public readonly ?string $batch_id,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $lot_number_manual,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $mfg_or_exp_date_manual,

        #[Required, StringType, Max(255)]
        public readonly string $company_name,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $company_address,

        #[Required, StringType, Max(150)]
        public readonly string $reporter_name,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $reporter_title,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $reporter_phone,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $reporter_fax,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $reporter_email,

        #[Nullable, StringType]
        public readonly ?string $ingredients_packaging,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $product_form_purpose,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $manufacturer_origin,

        #[Required, StringType, Max(150)]
        public readonly string $consumer_name,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $consumer_id_number,

        #[Nullable, Min(0)]
        public readonly ?int $consumer_age,

        public readonly ?ConsumerGender $consumer_gender,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $consumer_nationality,

        #[Nullable, Date]
        public readonly ?string $onset_at,

        #[Required, StringType]
        public readonly string $reaction_description,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $time_since_last_use,

        #[Nullable, StringType]
        public readonly ?string $usage_description,

        public readonly bool $was_hospitalized = false,

        public readonly bool $required_medical_treatment = false,

        public readonly ?AdverseEventOutcome $outcome,

        #[Nullable, Date]
        public readonly ?string $outcome_date,

        public readonly ?AdverseEventReportSource $report_source,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $report_source_detail,

        #[Required, Date]
        public readonly string $received_at,
    ) {}

    public static function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')->where('organization_id', TenantContext::getOrganizationId())],
            'batch_id'   => ['nullable', Rule::exists('batches', 'id')->where('organization_id', TenantContext::getOrganizationId())],
            'consumer_gender' => ['nullable', Rule::enum(ConsumerGender::class)],
            'outcome'         => ['nullable', Rule::enum(AdverseEventOutcome::class)],
            'report_source'   => ['nullable', Rule::enum(AdverseEventReportSource::class)],
        ];
    }

    public static function messages(): array
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'product_id.exists'   => 'Sản phẩm không hợp lệ.',
            'batch_id.exists'     => 'Lô hàng không hợp lệ.',

            'company_name.required' => 'Vui lòng nhập tên công ty.',
            'reporter_name.required' => 'Vui lòng nhập tên người thông báo.',

            'consumer_name.required' => 'Vui lòng nhập tên người sử dụng.',
            'consumer_age.min'       => 'Tuổi không hợp lệ.',
            'consumer_gender.enum'   => 'Giới tính không hợp lệ.',

            'reaction_description.required' => 'Vui lòng mô tả tác dụng bất lợi.',

            'outcome.enum'         => 'Kết quả không hợp lệ.',
            'report_source.enum'   => 'Nguồn báo cáo không hợp lệ.',

            'received_at.required' => 'Vui lòng nhập ngày nhận được khiếu nại.',
            'received_at.date'     => 'Ngày nhận khiếu nại không hợp lệ.',
        ];
    }
}
