<?php

namespace Modules\LabelTemplate\Data\Requests;

use Modules\LabelTemplate\Models\LabelTemplate;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateLabelTemplateData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required, StringType, Max(255), Regex(LabelTemplate::VIEW_PATH_REGEX)]
        public readonly string $view_path,

        #[Nullable, StringType, Max(2000)]
        public readonly ?string $description = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $default_size = null,
    ) {}

    public static function messages(): array
    {
        return StoreLabelTemplateData::messages();
    }
}
