<?php

namespace Modules\Product\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Product\Enums\DocumentGroupType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateDocumentMasterTypeData extends Data
{
    public function __construct(
        #[Required, StringType, Max(60), Regex('/^[a-z0-9_]+$/')]
        public readonly string $code,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly DocumentGroupType $document_group,

        public readonly bool $is_required_issue_date,

        public readonly bool $is_required_expiry_date,

        #[Nullable, Min(1), Max(600)]
        public readonly ?int $default_validity_months,
    ) {}

    public static function rules(): array
    {
        $currentId = request()->route('document_master_type')?->id;

        return [
            'code' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('document_master_types', 'code')->ignore($currentId),
            ],
        ];
    }

    public static function messages(): array
    {
        return StoreDocumentMasterTypeData::messages();
    }
}
