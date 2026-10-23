<?php

namespace Modules\GoodsReceipt\Http\Requests;

use App\Support\QuantityUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\GoodsReceipt\Models\GoodsReceipt;
use Modules\Product\Models\Product;

class SaveGoodsReceiptRequest extends FormRequest
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
                fn ($row) => is_array($row) && (filled($row['product_id'] ?? null) || filled($row['quantity'] ?? null)),
            ),
        ]);
    }

    public function rules(): array
    {
        $receipt = $this->route('goods_receipt');

        return [
            'misa_ref_id'        => ['nullable', 'string', 'max:50', 'regex:/^[\pL\pN\-_\/.]+$/u',
                Rule::unique('goods_receipts', 'misa_ref_id')->ignore($receipt instanceof GoodsReceipt ? $receipt->id : null)],
            'vendor_id'          => ['required', 'string', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'receipt_date'       => ['required', 'date'],
            'items'              => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', 'string', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity'   => ['required', 'numeric', 'gt:0', 'max:99999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'misa_ref_id.unique'          => 'Số phiếu này đã tồn tại.',
            'misa_ref_id.regex'           => 'Số phiếu chỉ gồm chữ, số và các ký tự - _ / .',
            'vendor_id.required'          => 'Vui lòng chọn nhà cung cấp từ danh mục.',
            'vendor_id.exists'            => 'Nhà cung cấp không có trong danh mục.',
            'receipt_date.required'       => 'Vui lòng chọn ngày nhập.',
            'receipt_date.date'           => 'Ngày nhập không hợp lệ.',
            'items.required'              => 'Vui lòng thêm ít nhất 1 dòng hàng hóa.',
            'items.min'                   => 'Vui lòng thêm ít nhất 1 dòng hàng hóa.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.product_id.exists'   => 'Sản phẩm không có trong danh mục.',
            'items.*.quantity.required'   => 'Vui lòng nhập số lượng.',
            'items.*.quantity.numeric'    => 'Số lượng phải là số.',
            'items.*.quantity.gt'         => 'Số lượng phải lớn hơn 0.',
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
                    $qty = $row['quantity'] ?? null;

                    if (! is_numeric($qty) || $validator->errors()->has("items.$key.quantity")) {
                        continue;
                    }

                    $unit = $units[$row['product_id'] ?? ''] ?? null;

                    if (! QuantityUnit::allowsDecimal($unit) && floor((float) $qty) != (float) $qty) {
                        $validator->errors()->add("items.$key.quantity", 'Đơn vị "' . ($unit ?: '—') . '" chỉ nhập số nguyên.');
                    } elseif (round((float) $qty, QuantityUnit::DECIMALS) != (float) $qty) {
                        $validator->errors()->add("items.$key.quantity", 'Tối đa ' . QuantityUnit::DECIMALS . ' chữ số thập phân.');
                    }
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function receiptAttributes(): array
    {
        return $this->safe()->only(['misa_ref_id', 'vendor_id', 'receipt_date']);
    }

    /** @return array<int, array{product_id: string, line_no: int, product_name_raw: ?string, unit_raw: ?string, quantity: float}> */
    public function receiptItems(): array
    {
        $merged = [];

        foreach ($this->validated('items') as $row) {
            $merged[$row['product_id']] = ($merged[$row['product_id']] ?? 0) + (float) $row['quantity'];
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
                'quantity'         => round($qty, 3),
            ];
        }

        return $items;
    }
}
