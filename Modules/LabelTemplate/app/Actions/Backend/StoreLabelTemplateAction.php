<?php

namespace Modules\LabelTemplate\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LabelTemplate\Data\Requests\StoreLabelTemplateData;
use Modules\LabelTemplate\Models\LabelTemplate;

class StoreLabelTemplateAction
{
    use AsAction;

    public function handle(StoreLabelTemplateData $data): LabelTemplate
    {
        return LabelTemplate::create([
            'name'         => $data->name,
            'view_path'    => $data->view_path,
            'description'  => $data->description,
            'default_size' => $data->default_size,
        ]);
    }
}
