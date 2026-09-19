<?php

namespace Modules\LabelTemplate\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\LabelTemplate\Models\LabelTemplate;

class PreviewLabelTemplateQuery implements QueryInterface
{
    public function __construct(
        public readonly LabelTemplate $labelTemplate,
    ) {}
}
