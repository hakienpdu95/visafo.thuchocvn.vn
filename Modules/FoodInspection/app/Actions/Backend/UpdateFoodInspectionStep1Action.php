<?php

namespace Modules\FoodInspection\Actions\Backend;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\FoodInspection\Data\Requests\Step1DetailData;
use Modules\FoodInspection\Data\Requests\StoreFoodInspectionStep1Data;
use Modules\FoodInspection\Models\FoodInspectionStep1Log;
use Modules\FoodInspection\Support\Step1RowAttributes;
use Modules\FoodInspection\Support\Step1VendorResolver;

class UpdateFoodInspectionStep1Action
{
    use AsAction;

    private const MAX_ATTACHMENTS = 10;

    /**
     * Đối chiếu lưới theo id: dòng có id → cập nhật; dòng không id → thêm mới; dòng cũ không còn trong dữ liệu gửi lên → xóa.
     * Luôn chạm updated_at của sổ (kể cả khi chỉ đổi dòng hàng) để làm vết "sửa lần cuối"; thay đổi chi tiết do Activity Log ghi lại.
     */
    public function handle(FoodInspectionStep1Log $log, StoreFoodInspectionStep1Data $data): FoodInspectionStep1Log
    {
        return DB::transaction(function () use ($log, $data) {
            $customer = Customer::query()->findOrFail($data->customer_id);
            $existing = $log->details()->get()->keyBy('id');
            $incoming = collect($data->details)->values();

            $foreign = $incoming->first(fn (Step1DetailData $d) => $d->id !== null && ! $existing->has($d->id));
            if ($foreign) {
                throw ValidationException::withMessages(['details' => 'Có dòng hàng không thuộc sổ kiểm thực này.']);
            }

            $keptFiles = collect($log->attachments ?? [])
                ->reject(fn (array $a) => in_array($a['path'], $data->remove_attachments, true))
                ->values();

            if ($keptFiles->count() + count($data->attachments) > self::MAX_ATTACHMENTS) {
                throw ValidationException::withMessages(['attachments' => 'Chỉ được đính kèm tối đa ' . self::MAX_ATTACHMENTS . ' chứng từ.']);
            }

            $newFiles = collect($data->attachments)
                ->map(fn (UploadedFile $f) => ['path' => $f->store('food-inspections', 'public'), 'name' => $f->getClientOriginalName()]);
            $attachments = $keptFiles->concat($newFiles)->values()->all();

            $log->fill([
                'customer_id'         => $customer->id,
                'customer_name'       => $customer->name,
                'inspected_at'        => $data->inspected_at,
                'inspection_location' => $data->inspection_location,
                'note'                => $data->note,
                'attachments'         => $attachments ?: null,
                'failed_items_count'  => $incoming->filter(fn (Step1DetailData $d) => $d->isFailed())->count(),
            ])->save();
            $log->touch();

            $vendorIds = app(Step1VendorResolver::class)->resolve($incoming);

            foreach ($incoming as $i => $detail) {
                $attributes = Step1RowAttributes::from($detail, $i + 1, $vendorIds[$i]);

                if ($detail->id !== null) {
                    $existing->get($detail->id)->update($attributes);
                } else {
                    $log->details()->create($attributes);
                }
            }

            $keptIds = $incoming->pluck('id')->filter()->all();
            $existing->reject(fn ($d) => in_array($d->id, $keptIds, true))->each->delete();

            return $log->refresh();
        });
    }
}
