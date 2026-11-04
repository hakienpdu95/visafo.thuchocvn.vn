<?php

namespace Modules\HanoiCheckOrder\Actions\Backend;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\HanoiCheckOrder\Data\Requests\SaveHanoiCheckDeliveryData;
use Modules\HanoiCheckOrder\Models\HanoiCheckOrderLink;
use Modules\HanoiCheckOrder\Models\HanoiCheckTransporter;

class SaveHanoiCheckDeliveryAction
{
    use AsAction;

    public function handle(HanoiCheckOrderLink $link, SaveHanoiCheckDeliveryData $data): HanoiCheckOrderLink
    {
        if ($data->transporter_id !== null && ! HanoiCheckTransporter::query()->whereKey($data->transporter_id)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['transporter_id' => 'Người giao hàng đã ngừng hoạt động.']);
        }

        $existing = $link->getMedia(HanoiCheckOrderLink::PHOTO_COLLECTION);
        $remaining = $existing->reject(fn ($m) => in_array((string) $m->id, array_map('strval', $data->remove_photo_ids), true))->count();
        if ($remaining + count($data->photos) > HanoiCheckOrderLink::MAX_PHOTOS) {
            throw ValidationException::withMessages(['photos' => 'Mỗi đơn chỉ có tối đa ' . HanoiCheckOrderLink::MAX_PHOTOS . ' ảnh tổng quan (HanoiCheck quy định).']);
        }

        $existing->filter(fn ($m) => in_array((string) $m->id, array_map('strval', $data->remove_photo_ids), true))->each->delete();
        foreach ($data->photos as $file) {
            $link->addMedia($file)->toMediaCollection(HanoiCheckOrderLink::PHOTO_COLLECTION);
        }

        $link->transporter_id = $data->transporter_id;
        $link->save();
        $link->unsetRelation('media')->unsetRelation('transporter');

        QueueHanoiCheckOrderSyncAction::run($link->sales_order_id, true);

        return $link;
    }
}
