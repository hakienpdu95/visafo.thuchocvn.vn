<?php

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status        = $this->status;
        $customerGroup = $this->customer_group;
        $mealModel     = $this->meal_model;

        return [
            'id'            => $this->id,
            'customer_code' => $this->customer_code,
            'name'          => $this->name,
            'tax_code'      => $this->tax_code,
            'phone_number'  => $this->phone_number,
            'email'         => $this->email,

            'customer_group_value' => $customerGroup->value,
            'customer_group_label' => $customerGroup->label(),

            'meal_model_value' => $mealModel->value,
            'meal_model_label' => $mealModel->label(),

            'status_value' => $status->value,
            'status_label' => $status->label(),
            'status_badge' => $status->badgeClass(),

            'pic_name' => $this->pic?->full_name,

            'created_at' => $this->created_at?->format('d/m/Y'),

            'show_url'   => route('backend.customers.show', $this->resource),
            'edit_url'   => route('backend.customers.edit', $this->resource),
            'delete_url' => route('backend.customers.destroy', $this->resource),
            'can_delete' => true,
        ];
    }
}
