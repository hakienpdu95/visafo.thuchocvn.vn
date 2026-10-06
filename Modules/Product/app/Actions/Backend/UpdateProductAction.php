<?php

namespace Modules\Product\Actions\Backend;

use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use App\Support\Html\RichHtmlSanitizer;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Data\Requests\UpdateProductData;
use Modules\Product\Models\Product;

class UpdateProductAction
{
    use AsAction;

    public function __construct(
        private readonly SyncProductGalleryAction $syncGallery,
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
    ) {}

    public function handle(Product $product, UpdateProductData $data): Product
    {
        $product->update([
            'sku'          => $data->sku,
            'name'         => $data->name,
            'description'  => $data->description,
            'product_info' => RichHtmlSanitizer::clean($data->product_info),
            'category_id'  => $data->category_id,
            'product_type' => $data->product_type->value,
            'unit'         => $data->unit,
            'shelf_life_days' => $data->shelf_life_days,
            'status'       => $data->status->value,
        ]);

        $this->syncProductInfoImages($product);

        if ($data->galleryIds() !== null) {
            $this->syncGallery->handle($product, $data->galleryIds(), $data->main_image);
        }

        return $product;
    }

    private function syncProductInfoImages(Product $product): void
    {
        preg_match_all('/data-media-uuid="([^"]+)"/', (string) $product->product_info, $m);
        $this->mediaUpload->reassociateOrphans($product, array_values(array_unique($m[1])));
        $refreshed = $this->mediaUrl->refreshEmbeddedImageUrls($product->product_info);
        if ($refreshed !== $product->product_info) {
            $product->update(['product_info' => $refreshed]);
        }
    }
}
