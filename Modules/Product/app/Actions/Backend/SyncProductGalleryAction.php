<?php

namespace Modules\Product\Actions\Backend;

use App\Models\FilePondDraft;
use App\Models\Media;
use App\Services\Media\MediaUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\Product;

/**
 * Đồng bộ bộ ảnh sản phẩm sau khi lưu form.
 *
 * - $mediaIds: danh sách ID ảnh còn giữ, theo thứ tự trên form (ảnh cũ của sản phẩm + ảnh mới upload qua FilePond draft).
 * - $mainId:   ảnh được chọn làm ảnh chính → được đưa lên order_column = 1 (slide đầu tiên).
 *
 * Ảnh chính không lưu bằng cờ riêng mà bằng thứ tự: getMedia('gallery') luôn sort theo order_column,
 * nên mọi nơi hiển thị tự nhận ảnh chính ở vị trí đầu mà không cần sortBy.
 */
class SyncProductGalleryAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $uploadService) {}

    /** @param  string[]  $mediaIds */
    public function handle(Product $product, array $mediaIds, ?string $mainId = null): void
    {
        $allowed = self::allowedMedia($product, $mediaIds)->keyBy('id');

        // Giữ đúng thứ tự client gửi, bỏ ID không hợp lệ / trùng lặp
        $ordered = collect($mediaIds)->unique()->filter(fn ($id) => $allowed->has($id))->values();

        if ($mainId !== null && $ordered->contains($mainId)) {
            $ordered = $ordered->reject(fn ($id) => $id === $mainId)->prepend($mainId)->values();
        }

        // Draft (vừa upload trên form) → gắn hẳn vào sản phẩm
        foreach ($ordered as $id) {
            if ($allowed[$id]->model_type === FilePondDraft::class) {
                $this->moveToProduct($allowed[$id], $product);
            }
        }

        // Ảnh bị gỡ khỏi form → xóa cả file trên disk
        $product->media()
            ->where('collection_name', Product::GALLERY_COLLECTION)
            ->whereNotIn('id', $ordered->all())
            ->get()
            ->each(fn (Media $m) => $this->uploadService->delete($m));

        if ($ordered->isNotEmpty()) {
            Media::setNewOrder($ordered->all());
        }

        $product->unsetRelation('media');
    }

    /**
     * MediaPathGenerator dựng đường dẫn theo model (media/{module}/{entity}/{id}/{media_id}),
     * nên đổi model_type/model_id phải chuyển cả thư mục file (gốc + conversions) theo, nếu không URL sẽ 404.
     */
    private function moveToProduct(Media $media, Product $product): void
    {
        $disk   = Storage::disk($media->disk);
        $oldDir = rtrim(dirname($media->getPathRelativeToRoot()), '/');

        $media->model_type = $product->getMorphClass();
        $media->model_id   = $product->getKey();

        $newDir = rtrim(dirname($media->getPathRelativeToRoot()), '/');

        foreach ($disk->files($oldDir) as $file) {
            $disk->move($file, $newDir . '/' . basename($file));
        }

        $media->save();

        $this->uploadService->pruneEmptyAncestors($media->disk, $oldDir);
    }

    /**
     * Chỉ chấp nhận ảnh gallery đang thuộc chính sản phẩm này, hoặc draft do user hiện tại upload.
     * Chặn việc gửi ID media của sản phẩm/tenant khác để "chiếm" file.
     *
     * Dùng chung cho controller khi dựng lại danh sách ảnh sau lỗi validate (old input).
     *
     * @param  string[]  $mediaIds
     * @return Collection<int, Media>
     */
    public static function allowedMedia(?Product $product, array $mediaIds): Collection
    {
        if ($mediaIds === []) {
            return collect();
        }

        $draftIds = FilePondDraft::query()->where('user_id', auth()->id())->pluck('id');

        return Media::query()
            ->whereIn('id', $mediaIds)
            ->where('collection_name', Product::GALLERY_COLLECTION)
            ->where(function (Builder $q) use ($product, $draftIds) {
                $q->where(fn (Builder $d) => $d->where('model_type', FilePondDraft::class)->whereIn('model_id', $draftIds));

                if ($product?->exists) {
                    $q->orWhere(fn (Builder $p) => $p->where('model_type', $product->getMorphClass())->where('model_id', $product->getKey()));
                }
            })
            ->get();
    }
}
