<?php

namespace Modules\LabelTemplate\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LabelTemplate\Data\Requests\UpdateLabelTemplateData;
use Modules\LabelTemplate\Models\LabelTemplate;

class UpdateLabelTemplateAction
{
    use AsAction;

    public function handle(LabelTemplate $labelTemplate, UpdateLabelTemplateData $data): LabelTemplate
    {
        $labelTemplate->update([
            'name'         => $data->name,
            'view_path'    => $data->view_path,
            'description'  => $data->description,
            'default_size' => $data->default_size,
        ]);

        return $labelTemplate;
    }
}
