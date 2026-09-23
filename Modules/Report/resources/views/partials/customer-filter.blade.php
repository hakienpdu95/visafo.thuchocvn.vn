<div class="form-control">
    <label class="label py-0.5"><span class="label-text text-xs font-medium">Khách hàng</span></label>
    <select id="rp-customer" data-ts-placeholder="Tất cả khách hàng" class="select select-sm select-bordered w-full">
        <option value="">Tất cả</option>
        @foreach($customers as $customer)
        <option value="{{ $customer['value'] }}">{{ $customer['text'] }}</option>
        @endforeach
    </select>
</div>
