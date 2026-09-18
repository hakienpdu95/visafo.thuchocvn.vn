@extends('layouts.backend')
@section('title', 'Import phiếu nhập kho')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Import phiếu nhập kho (MISA)</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tải lên 1 hoặc nhiều file Excel Mẫu "01 - VT Phiếu nhập kho"</p>
    </div>
    <a href="{{ route('backend.goods-receipts.index') }}" class="btn btn-ghost btn-sm">Danh sách phiếu nhập</a>
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
                    <tr>
                        <th>File</th>
                        <th>Số phiếu</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['results'] as $result)
                    <tr>
                        <td class="font-mono text-xs">{{ $result['file_name'] }}</td>
                        <td class="font-mono text-xs">{{ $result['misa_ref_id'] ?? '—' }}</td>
                        <td>
                            @if($result['status'] === 'imported')
                            <span class="badge badge-success badge-sm">Đã import ({{ $result['items_count'] }} dòng)</span>
                            @elseif($result['status'] === 'duplicate')
                            <span class="badge badge-warning badge-sm">Trùng — đã bỏ qua</span>
                            @else
                            <span class="badge badge-error badge-sm">Lỗi</span>
                            @endif
                        </td>
                        <td class="text-xs text-base-content/60">{{ $result['message'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200 max-w-2xl">
    <div class="card-body p-5">
        <form method="POST" action="{{ route('backend.goods-receipts.import.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-control mb-4">
                <label class="label pb-1">
                    <span class="label-text font-semibold">File Excel (.xls, .xlsx) — có thể chọn nhiều file</span>
                </label>
                <input type="file" name="files[]" multiple accept=".xls,.xlsx"
                       class="file-input file-input-bordered w-full" required>
                @error('files')
                <span class="text-error text-xs mt-1">{{ $message }}</span>
                @enderror
                @error('files.*')
                <span class="text-error text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Import</button>
        </form>
    </div>
</div>
@endsection
