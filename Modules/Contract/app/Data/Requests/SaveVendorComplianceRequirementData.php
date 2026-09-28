<?php

namespace Modules\Contract\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Contract\Enums\ComplianceRequirementKind;
use Modules\Vendor\Enums\VendorSourceGroup;
use Spatie\LaravelData\Data;

class SaveVendorComplianceRequirementData extends Data
{
    public function __construct(
        public readonly ComplianceRequirementKind $kind,
        public readonly array $target_ids,
        public readonly string $label,
        public readonly ?VendorSourceGroup $source_group = null,
        public readonly bool $is_mandatory = true,
        public readonly int $warning_days = 30,
        public readonly ?string $legal_basis = null,
    ) {}

    public static function rules(): array
    {
        $table = request('kind') === ComplianceRequirementKind::Contract->value ? 'contract_types' : 'document_master_types';

        return [
            'kind'          => ['required', Rule::enum(ComplianceRequirementKind::class)],
            'target_ids'    => ['required', 'array', 'min:1'],
            'target_ids.*'  => ['required', 'string', 'distinct', Rule::exists($table, 'id')->whereNull('deleted_at')],
            'label'         => ['required', 'string', 'max:255'],
            'source_group'  => ['nullable', Rule::enum(VendorSourceGroup::class)],
            'is_mandatory'  => ['boolean'],
            'warning_days'  => ['required', 'integer', 'min:1', 'max:365'],
            'legal_basis'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function messages(): array
    {
        return [
            'kind.required'         => 'Vui lòng chọn loại yêu cầu.',
            'target_ids.required'   => 'Vui lòng chọn ít nhất một loại hồ sơ / hợp đồng.',
            'target_ids.min'        => 'Vui lòng chọn ít nhất một loại hồ sơ / hợp đồng.',
            'target_ids.*.exists'   => 'Loại hồ sơ / hợp đồng không hợp lệ.',
            'target_ids.*.distinct' => 'Không được chọn trùng loại hồ sơ / hợp đồng.',
            'label.required'        => 'Vui lòng nhập tên yêu cầu.',
            'label.max'             => 'Tên yêu cầu không được vượt quá 255 ký tự.',
            'source_group.enum'     => 'Nhóm nguồn không hợp lệ.',
            'warning_days.required' => 'Vui lòng nhập số ngày cảnh báo.',
            'warning_days.min'      => 'Số ngày cảnh báo tối thiểu 1.',
            'warning_days.max'      => 'Số ngày cảnh báo tối đa 365.',
            'legal_basis.max'       => 'Căn cứ pháp lý không được vượt quá 255 ký tự.',
        ];
    }
}
