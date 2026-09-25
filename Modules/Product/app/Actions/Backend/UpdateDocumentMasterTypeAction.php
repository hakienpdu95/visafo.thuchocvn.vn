<?php

namespace Modules\Product\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\UpdateDocumentMasterTypeData;
use Modules\Product\Models\DocumentMasterType;

class UpdateDocumentMasterTypeAction
{
    use AsAction;

    public function handle(DocumentMasterType $documentMasterType, UpdateDocumentMasterTypeData $data): DocumentMasterType
    {
        $documentMasterType->update([
            'code'                     => $data->code,
            'name'                     => $data->name,
            'document_group'           => $data->document_group->value,
            'is_required_issue_date'   => $data->is_required_issue_date,
            'is_required_expiry_date'  => $data->is_required_expiry_date,
            'default_validity_months'  => $data->default_validity_months,
        ]);

        return $documentMasterType;
    }
}
