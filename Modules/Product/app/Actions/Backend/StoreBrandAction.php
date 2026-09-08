<?php

namespace Modules\Product\Actions\Backend;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\StoreBrandData;
use Modules\Product\Models\Brand;

class StoreBrandAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $mediaUploadService) {}

    public function handle(StoreBrandData $data, ?string $logoMediaUuid = null): Brand
    {
        $brand = Brand::create([
            'name'        => $data->name,
            'description' => $data->description,
        ]);

        if ($logoMediaUuid) {
            $this->mediaUploadService->reassociateFilePondDrafts($brand, [$logoMediaUuid], 'logo');
        }

        return $brand;
    }
}
