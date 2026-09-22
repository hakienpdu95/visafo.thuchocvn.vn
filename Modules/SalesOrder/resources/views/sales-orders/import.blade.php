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

@if(session('import_summary'))
@php($summary = session('import_summary'))
<div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
    <div class="card-body p-5">
        <h2 class="font-bold text-base-content mb-3">Kết quả import</h2>
        <p class="text-sm mb-4">{{ $summary['summary_message'] }}</p>

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
                    @foreach($summary['results'] as $index => $result)
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 text-center">{{ $index + 1 }}</td>
                        <td class="border border-gray-300 px-3 py-2 font-mono break-all">{{ $result['file_name'] }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-center">
                            @if($result['status'] === 'imported')
                                <span class="badge badge-success badge-sm">Thành công</span>
                            @elseif($result['status'] === 'duplicate')
                                <span class="badge badge-warning badge-sm">Trùng lặp</span>
                            @else
                                <span class="badge badge-error badge-sm">Lỗi</span>
                            @endif
                        </td>
                        <td class="border border-gray-300 px-3 py-2">{{ $result['misa_ref_id'] ?? '—' }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right">{{ $result['items_count'] ?: '—' }}</td>
                        <td class="border border-gray-300 px-3 py-2 {{ $result['status'] === 'failed' ? 'text-error' : 'text-base-content/70' }}">
                            {{ $result['message'] ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

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
        <form method="POST" action="{{ route('backend.sales-orders.import.store') }}" enctype="multipart/form-data" class="border-t border-gray-200 pt-6">
            @csrf

            <div class="mb-6 max-w-xl">
                <label for="files" class="block text-sm font-medium text-gray-800 mb-2">
                    <span class="text-red-600">*</span> Chọn tập tin Excel (.xls, .xlsx)
                </label>
                <input type="file" id="files" name="files[]" multiple accept=".xls,.xlsx" required
                       class="file-input file-input-bordered w-full">
                @error('files')
                <span class="block text-error text-xs mt-1">{{ $message }}</span>
                @enderror
                @error('files.*')
                <span class="block text-error text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">Nhập dữ liệu</button>
                <a href="{{ route('backend.sales-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 active:text-gray-800 active:bg-gray-50 transition ease-in-out duration-150">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>
@endsection
