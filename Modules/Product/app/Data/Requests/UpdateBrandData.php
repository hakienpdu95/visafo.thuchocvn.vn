<?php

namespace Modules\Product\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateBrandData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $name,

        #[Nullable, StringType, Max(1000)]
        public readonly ?string $description,
    ) {}

    public static function rules(): array
    {
        $currentId = request()->route('brand')?->id;

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('brands', 'name')
                    ->where('organization_id', TenantContext::getOrganizationId())
                    ->ignore($currentId),
            ],
        ];
    }

    public static function messages(): array
    {
        return StoreBrandData::messages();
    }
}
