@extends('layouts.backend')
@section('title', 'Import phiếu xuất kho')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Import phiếu xuất kho (MISA)</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tải lên 1 hoặc nhiều file Excel Mẫu "02 - VT Phiếu xuất kho"</p>
    </div>
    <a href="{{ route('backend.sales-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 active:text-gray-800 active:bg-gray-50 transition ease-in-out duration-150">Danh sách đơn xuất hàng</a>
</div>

<div x-data="salesOrderImport({{ Js::from([
    'url'     => route('backend.sales-orders.import.store'),
    'limits'  => $uploadLimits,
    'summary' => session('import_summary'),
]) }})">

<template x-if="summary">
<div class="card bg-base-100 shadow-sm border border-base-200 mb-6" x-ref="summary">
    <div class="card-body p-5">
        <h2 class="font-bold text-base-content mb-3">Kết quả import <span class="text-sm font-normal text-base-content/50" x-text="'(' + summary.results.length + ' file)'"></span></h2>
        <p class="text-sm mb-4" x-text="summary.summary_message"></p>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="bg-gray-100 font-bold text-gray-800">
                        <th class="border border-gray-300 px-3 py-2 text-center">STT</th>
                        <th class="border border-gray-300 px-3 py-2 text-left">Tên file</th>
                        <th class="border border-gray-300 px-3 py-2 text-center">Trạng thái</th>
                        <th class="border border-gray-300 px-3 py-2 text-left">Số chứng từ</th>
                        <th class="border border-gray-300 px-3 py-2 text-right">Số mặt hàng</th>
                        <th class="border border-gray-300 px-3 py-2 text-left">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(result, index) in summary.results" :key="index">
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 text-center" x-text="index + 1"></td>
                        <td class="border border-gray-300 px-3 py-2 font-mono break-all" x-text="result.file_name"></td>
                        <td class="border border-gray-300 px-3 py-2 text-center">
                            <span class="badge badge-success badge-sm" x-show="result.status === 'imported'">Thành công</span>
                            <span class="badge badge-warning badge-sm" x-show="result.status === 'duplicate'">Trùng lặp</span>
                            <span class="badge badge-error badge-sm" x-show="result.status === 'failed'">Lỗi</span>
                        </td>
                        <td class="border border-gray-300 px-3 py-2" x-text="result.misa_ref_id || '—'"></td>
                        <td class="border border-gray-300 px-3 py-2 text-right" x-text="result.items_count || '—'"></td>
                        <td class="border border-gray-300 px-3 py-2" :class="result.status === 'failed' ? 'text-error' : 'text-base-content/70'"
                            x-text="result.message || '—'"></td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
</template>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-6 md:p-8 gap-8">

        {{-- 1. Nút chức năng --}}
        <div>
            {{-- TODO: thay "#" bằng file mẫu tĩnh trong public/ khi có (vd: asset('templates/mau-02-vt.xlsx')) --}}
            <a href="#" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">Tải file mẫu (Mẫu 02-VT)</a>
        </div>

        {{-- 2. Hướng dẫn & lưu ý --}}
        <div class="rounded-md border border-amber-200 bg-amber-50 p-4 md:p-5">
            <h2 class="text-sm font-semibold text-amber-900 mb-2">Hướng dẫn &amp; lưu ý</h2>
            <ol class="list-decimal list-outside pl-5 space-y-1.5 text-sm text-gray-700 leading-relaxed">
                <li>Tệp dữ liệu Excel (XLS, XLSX) phải được xuất chuẩn từ phần mềm Kế toán MISA (Mẫu 02 - VT Phiếu xuất kho).</li>
                <li>Hệ thống hỗ trợ tải lên <strong>nhiều file (1-n)</strong> cùng một lúc. Mỗi file sẽ được tạo thành một Đơn xuất hàng riêng biệt (trạng thái chờ xử lý).</li>
                <li>Các phiếu xuất đã tồn tại (trùng Số chứng từ MISA) sẽ bị hệ thống <strong>từ chối</strong> để tránh trùng lặp dữ liệu.</li>
                <li>Các mặt hàng lặp lại trong cùng một phiếu sẽ được hệ thống <strong>tự động gộp nhóm và cộng dồn</strong> số lượng yêu cầu. Cột "Thực xuất" trong file được bỏ qua — kho sẽ cân và nhập sau.</li>
                <li>Các mã hàng mới chưa có trong danh mục Master Data sẽ được hệ thống <strong>tự động tạo mới</strong>.</li>
            </ol>
        </div>

        {{-- 3. Bảng mô phỏng cấu trúc file --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-800 mb-2">Cấu trúc file Excel chuẩn</h2>
            <div class="overflow-x-auto rounded-md border border-gray-300">
                <table class="w-full min-w-[800px] border-collapse text-xs text-gray-700">
                    <thead>
                        <tr class="bg-gray-100 font-bold text-gray-800">
                            <th class="border border-gray-300 px-3 py-2 text-center">STT</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Tên, nhãn hiệu...</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Mã số <span class="text-red-600">(Bắt buộc)</span></th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Đơn vị tính</th>
                            <th class="border border-gray-300 px-3 py-2 text-right">Số lượng (Theo chứng từ)</th>
                            <th class="border border-gray-300 px-3 py-2 text-right">Đơn giá</th>
                            <th class="border border-gray-300 px-3 py-2 text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-300 px-3 py-2 text-center">1</td>
                            <td class="border border-gray-300 px-3 py-2">Bánh gạo mật ong ICHI 180g</td>
                            <td class="border border-gray-300 px-3 py-2 font-mono">HH00294</td>
                            <td class="border border-gray-300 px-3 py-2">Gói</td>
                            <td class="border border-gray-300 px-3 py-2 text-right">10.5</td>
                            <td class="border border-gray-300 px-3 py-2 text-right">...</td>
                            <td class="border border-gray-300 px-3 py-2 text-right">...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. Form upload --}}
        <form method="POST" action="{{ route('backend.sales-orders.import.store') }}" enctype="multipart/form-data" class="border-t border-gray-200 pt-6"
              @submit.prevent="submit($event)" novalidate>
            @csrf

            <div class="mb-4 max-w-xs">
                <label for="delivery_date" class="block text-sm font-medium text-gray-800 mb-2">
                    <span class="text-red-600">*</span> Ngày giao hàng
                </label>
                <input type="text" id="delivery_date" name="delivery_date" required
                       value="{{ old('delivery_date', $defaultDeliveryDate) }}"
                       class="input input-bordered w-full fp-init @error('delivery_date') input-error @enderror">
                <span class="block text-xs text-gray-500 mt-1">Áp dụng cho mọi phiếu trong lần nhập này.</span>
                @error('delivery_date')
                <span class="block text-error text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-6 max-w-xl">
                <label for="files" class="block text-sm font-medium text-gray-800 mb-2">
                    <span class="text-red-600">*</span> Chọn tập tin Excel (.xls, .xlsx)
                </label>
                <input type="file" id="files" name="files[]" multiple accept=".xls,.xlsx" required
                       @change="onFilesChange($event)" :disabled="submitting"
                       class="file-input file-input-bordered w-full">
                <span class="block text-xs text-gray-500 mt-1" x-show="selectedCount > 0" x-text="'Đã chọn ' + selectedCount + ' file.'"></span>
                @error('files')
                <span class="block text-error text-xs mt-1">{{ $message }}</span>
                @enderror
                @error('files.*')
                <span class="block text-error text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <p class="text-sm text-error mb-3" x-show="error" x-text="error"></p>
            <p class="text-sm text-info mb-3" x-show="progress" x-text="progress"></p>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="submitting" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <span class="loading loading-spinner loading-xs mr-1.5" x-show="submitting"></span>
                    <span x-text="submitting ? 'Đang nhập dữ liệu...' : 'Nhập dữ liệu'">Nhập dữ liệu</span>
                </button>
                <a href="{{ route('backend.sales-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 active:text-gray-800 active:bg-gray-50 transition ease-in-out duration-150">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/modules/flatpickr.js', 'Modules/SalesOrder/resources/assets/js/salesorder.js'], 'build/backend')
@endpush
