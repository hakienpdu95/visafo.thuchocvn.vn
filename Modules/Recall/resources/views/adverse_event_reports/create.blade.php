@extends('layouts.backend')
@section('title', 'Ghi nhận khiếu nại tác dụng bất lợi')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Ghi nhận khiếu nại tác dụng bất lợi</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Các trường khớp theo Phụ lục 18-MP (Thông tư 06/2011/TT-BYT)</p>
    </div>
    <a href="{{ route('backend.adverse-event-reports.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.adverse-event-reports.store') }}" novalidate>
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-4xl">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body space-y-3">
                <h2 class="text-base font-semibold">I. Thông tin về công ty</h2>

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên công ty <span class="text-error">*</span></span></label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" class="input input-bordered input-sm w-full">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Địa chỉ công ty</span></label>
                    <input type="text" name="company_address" value="{{ old('company_address') }}" class="input input-bordered input-sm w-full">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên người thông báo <span class="text-error">*</span></span></label>
                    <input type="text" name="reporter_name" value="{{ old('reporter_name') }}" class="input input-bordered input-sm w-full">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Chức danh</span></label>
                    <input type="text" name="reporter_title" value="{{ old('reporter_title') }}" class="input input-bordered input-sm w-full">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số điện thoại</span></label>
                        <input type="text" name="reporter_phone" value="{{ old('reporter_phone') }}" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Fax</span></label>
                        <input type="text" name="reporter_fax" value="{{ old('reporter_fax') }}" class="input input-bordered input-sm w-full">
                    </div>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Email</span></label>
                    <input type="email" name="reporter_email" value="{{ old('reporter_email') }}" class="input input-bordered input-sm w-full">
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200" x-data="tagLookup()">
            <div class="card-body space-y-3">
                <h2 class="text-base font-semibold">II. Thông tin sản phẩm</h2>

                <div class="bg-base-200/50 rounded-lg p-3 space-y-2">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Quét mã QR hoặc nhập Serial GS1 trên tem hộp lỗi</span></label>
                    <div class="flex gap-2">
                        <input type="text" x-model="code" @keydown.enter.prevent="lookup()" placeholder="Quét mã QR hoặc gõ serial..." class="input input-bordered input-sm w-full font-mono">
                        <button type="button" @click="lookup()" class="btn btn-sm btn-outline" :disabled="loading">Tra cứu</button>
                    </div>
                    <p class="text-xs" :class="result?.found ? 'text-success' : 'text-error'" x-show="result" x-text="message"></p>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Sản phẩm <span class="text-error">*</span></span></label>
                    <select name="product_id" x-ref="productSelect" class="select select-bordered select-sm w-full">
                        @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') === $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Lô hàng (nếu xác định được)</span></label>
                    <input type="text" name="batch_id" x-ref="batchIdInput" value="{{ old('batch_id') }}" placeholder="ID lô hàng — tự điền khi tra cứu tem" class="input input-bordered input-sm w-full font-mono">
                    <p class="text-xs text-base-content/50 mt-1" x-show="result?.found && result.batch_code">
                        Lô <span x-text="result?.batch_code" class="font-mono"></span> ·
                        NSX <span x-text="result?.mfg_date"></span> · HSD <span x-text="result?.exp_date"></span>
                    </p>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Số lô (nhập tay nếu không có ID lô)</span></label>
                    <input type="text" name="lot_number_manual" value="{{ old('lot_number_manual') }}" class="input input-bordered input-sm w-full">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày sản xuất hoặc hạn dùng (nhập tay nếu không có ID lô)</span></label>
                    <input type="text" name="mfg_or_exp_date_manual" value="{{ old('mfg_or_exp_date_manual') }}" placeholder="VD: HSD 12/2027" class="input input-bordered input-sm w-full">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Danh sách thành phần, dạng đóng gói</span></label>
                    <textarea name="ingredients_packaging" rows="2" class="textarea textarea-bordered textarea-sm w-full">{{ old('ingredients_packaging') }}</textarea>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Dạng sản phẩm/mục đích sử dụng</span></label>
                    <input type="text" name="product_form_purpose" value="{{ old('product_form_purpose') }}" class="input input-bordered input-sm w-full">
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên công ty sản xuất/xuất xứ</span></label>
                    <input type="text" name="manufacturer_origin" x-ref="manufacturerInput" value="{{ old('manufacturer_origin') }}" class="input input-bordered input-sm w-full">
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200 lg:col-span-2">
            <div class="card-body space-y-3">
                <h2 class="text-base font-semibold">III. Báo cáo tác dụng bất lợi chi tiết</h2>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên người sử dụng <span class="text-error">*</span></span></label>
                        <input type="text" name="consumer_name" value="{{ old('consumer_name') }}" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">CMND/Hộ chiếu</span></label>
                        <input type="text" name="consumer_id_number" value="{{ old('consumer_id_number') }}" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tuổi</span></label>
                        <input type="number" name="consumer_age" value="{{ old('consumer_age') }}" min="0" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Giới tính</span></label>
                        <select name="consumer_gender" class="select select-bordered select-sm w-full">
                            <option value="">—</option>
                            @foreach(\Modules\Recall\Enums\ConsumerGender::cases() as $gender)
                            <option value="{{ $gender->value }}" @selected(old('consumer_gender') === $gender->value)>{{ $gender->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tôn giáo/Quốc tịch</span></label>
                        <input type="text" name="consumer_nationality" value="{{ old('consumer_nationality') }}" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Thời gian xuất hiện tác dụng bất lợi</span></label>
                        <input type="datetime-local" name="onset_at" value="{{ old('onset_at') }}" class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Mô tả tác dụng bất lợi <span class="text-error">*</span></span></label>
                    <textarea name="reaction_description" rows="3" class="textarea textarea-bordered textarea-sm w-full">{{ old('reaction_description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Thời gian giữa lần dùng cuối và lúc xuất hiện phản ứng</span></label>
                        <input type="text" name="time_since_last_use" value="{{ old('time_since_last_use') }}" placeholder="VD: 2 giờ 30 phút" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Sản phẩm đã được sử dụng như thế nào</span></label>
                        <input type="text" name="usage_description" value="{{ old('usage_description') }}" class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-2 py-0">
                            <input type="checkbox" name="was_hospitalized" value="1" class="checkbox checkbox-sm" @checked(old('was_hospitalized'))>
                            <span class="label-text text-sm">Người sử dụng phải nhập viện</span>
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-2 py-0">
                            <input type="checkbox" name="required_medical_treatment" value="1" class="checkbox checkbox-sm" @checked(old('required_medical_treatment'))>
                            <span class="label-text text-sm">Phải điều trị y tế</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Kết quả</span></label>
                        <select name="outcome" class="select select-bordered select-sm w-full">
                            <option value="">—</option>
                            @foreach(\Modules\Recall\Enums\AdverseEventOutcome::cases() as $outcome)
                            <option value="{{ $outcome->value }}" @selected(old('outcome') === $outcome->value)>{{ $outcome->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày hồi phục/tử vong</span></label>
                        <input type="date" name="outcome_date" value="{{ old('outcome_date') }}" class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Nguồn cung cấp báo cáo</span></label>
                        <select name="report_source" class="select select-bordered select-sm w-full">
                            <option value="">—</option>
                            @foreach(\Modules\Recall\Enums\AdverseEventReportSource::cases() as $source)
                            <option value="{{ $source->value }}" @selected(old('report_source') === $source->value)>{{ $source->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Chi tiết nguồn (ghi rõ)</span></label>
                        <input type="text" name="report_source_detail" value="{{ old('report_source_detail') }}" class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="form-control max-w-xs">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ngày công ty nhận được khiếu nại <span class="text-error">*</span></span></label>
                    <input type="date" name="received_at" value="{{ old('received_at', now()->format('Y-m-d')) }}" class="input input-bordered input-sm w-full">
                </div>
            </div>
            <div class="card-body pt-0 flex-row justify-end gap-2 border-t border-base-200">
                <a href="{{ route('backend.adverse-event-reports.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                <button type="submit" class="btn btn-primary btn-sm">Lưu khiếu nại</button>
            </div>
        </div>

    </div>
</form>

<script>
function tagLookup() {
    return {
        code: '',
        loading: false,
        result: null,
        message: '',
        lookup() {
            if (!this.code.trim()) return;
            this.loading = true;

            fetch('{{ route('backend.adverse-event-reports.lookup-tag') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ code: this.code.trim() }),
            })
            .then(r => r.json())
            .then(data => {
                this.result = data;
                if (data.found) {
                    this.message = `Đã tìm thấy: ${data.product_name}${data.batch_code ? ' — Lô ' + data.batch_code : ''}`;
                    this.$refs.productSelect.value = data.product_id;
                    if (data.batch_id) this.$refs.batchIdInput.value = data.batch_id;
                    if (data.manufacturer_origin) this.$refs.manufacturerInput.value = data.manufacturer_origin;
                } else {
                    this.message = data.message ?? 'Không tìm thấy tem này.';
                }
            })
            .catch(() => {
                this.result = { found: false };
                this.message = 'Lỗi kết nối — thử lại.';
            })
            .finally(() => { this.loading = false; });
        },
    };
}
</script>
@endsection
