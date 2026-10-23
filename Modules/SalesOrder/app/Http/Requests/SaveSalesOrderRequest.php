<?php

namespace Modules\SalesOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Models\SalesOrder;
use App\Support\QuantityUnit;

class SaveSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ref = preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', '', (string) $this->input('misa_ref_id'));

        $this->merge([
            'misa_ref_id' => $ref !== '' ? mb_strtoupper($ref) : null,
            'items'       => array_filter(
                (array) $this->input('items', []),
                fn ($row) => is_array($row) && (filled($row['product_id'] ?? null) || filled($row['requested_qty'] ?? null)),
            ),
        ]);
    }

    public function rules(): array
    {
        $order = $this->route('sales_order');

        return [
            'misa_ref_id'           => ['nullable', 'string', 'max:50', 'regex:/^[\pL\pN\-_\/.]+$/u',
                Rule::unique('sales_orders', 'misa_ref_id')->ignore($order instanceof SalesOrder ? $order->id : null)],
            'customer_id'           => ['nullable', 'string', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'customer_name'         => ['required', 'string', 'max:100'],
            'delivery_address'      => ['nullable', 'string', 'max:200'],
            'delivery_date'         => ['required', 'date'],
            'items'                 => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id'    => ['required', 'string', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.requested_qty' => ['required', 'numeric', 'gt:0', 'max:99999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'misa_ref_id.unique'             => 'Số phiếu này đã tồn tại.',
            'misa_ref_id.regex'              => 'Số phiếu chỉ gồm chữ, số và các ký tự - _ / .',
            'customer_id.exists'             => 'Khách hàng không tồn tại.',
            'customer_name.required'         => 'Vui lòng nhập tên khách hàng / người nhận.',
            'customer_name.max'              => 'Tên khách hàng / người nhận tối đa 100 ký tự (giới hạn hiển thị trên tem).',
            'delivery_address.max'           => 'Địa chỉ giao hàng tối đa 200 ký tự (giới hạn hiển thị trên tem).',
            'delivery_date.required'         => 'Vui lòng chọn ngày giao hàng.',
            'delivery_date.date'             => 'Ngày giao hàng không hợp lệ.',
            'items.required'                 => 'Vui lòng thêm ít nhất 1 dòng hàng hóa.',
            'items.min'                      => 'Vui lòng thêm ít nhất 1 dòng hàng hóa.',
            'items.*.product_id.required'    => 'Vui lòng chọn sản phẩm.',
            'items.*.product_id.exists'      => 'Sản phẩm không có trong danh mục.',
            'items.*.requested_qty.required' => 'Vui lòng nhập số lượng.',
            'items.*.requested_qty.numeric'  => 'Số lượng phải là số.',
            'items.*.requested_qty.gt'       => 'Số lượng phải lớn hơn 0.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $items = (array) $this->input('items', []);
                $units = Product::query()
                    ->whereIn('id', collect($items)->pluck('product_id')->filter()->all())
                    ->pluck('unit', 'id');

                foreach ($items as $key => $row) {
                    $qty = $row['requested_qty'] ?? null;

                    if (! is_numeric($qty) || $validator->errors()->has("items.$key.requested_qty")) {
                        continue;
                    }

                    $unit = $units[$row['product_id'] ?? ''] ?? null;

                    if (! QuantityUnit::allowsDecimal($unit) && floor((float) $qty) != (float) $qty) {
                        $validator->errors()->add("items.$key.requested_qty", 'Đơn vị "' . ($unit ?: '—') . '" chỉ nhập số nguyên.');
                    } elseif (round((float) $qty, QuantityUnit::DECIMALS) != (float) $qty) {
                        $validator->errors()->add("items.$key.requested_qty", 'Tối đa ' . QuantityUnit::DECIMALS . ' chữ số thập phân.');
                    }
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function orderAttributes(): array
    {
        return $this->safe()->only(['misa_ref_id', 'customer_id', 'customer_name', 'delivery_address', 'delivery_date']);
    }

    /** @return array<int, array{product_id: string, line_no: int, product_name_raw: ?string, unit_raw: ?string, requested_qty: float}> */
    public function orderItems(): array
    {
        $merged = [];

        foreach ($this->validated('items') as $row) {
            $merged[$row['product_id']] = ($merged[$row['product_id']] ?? 0) + (float) $row['requested_qty'];
        }

        $products = Product::query()->whereIn('id', array_keys($merged))->get(['id', 'name', 'unit'])->keyBy('id');
        $items = [];

        foreach ($merged as $productId => $qty) {
            $product = $products->get($productId);
            $items[] = [
                'product_id'       => $productId,
                'line_no'          => count($items) + 1,
                'product_name_raw' => $product?->name,
                'unit_raw'         => $product?->unit !== '' ? $product?->unit : null,
                'requested_qty'    => round($qty, 3),
            ];
        }

        return $items;
    }
}
