@extends('layouts.backend')
@section('title', 'Danh mục Nhóm hàng')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Danh mục Nhóm hàng</h1>
        <p class="text-sm text-base-content/50 mt-0.5">6 nhóm thực phẩm chuẩn ATTP — dùng để gán cho Sản phẩm và kích hoạt yêu cầu hồ sơ pháp lý tương ứng</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm">Quay lại danh mục</a>
        @can('create', \Modules\Product\Models\Category::class)
        <a href="{{ route('backend.categories.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm nhóm hàng
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0 overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên nhóm</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td class="font-mono text-xs">{{ $category->code }}</td>
                    <td class="font-medium">{{ $category->name }}</td>
                    <td class="text-sm text-base-content/60">{{ $category->description ?? '—' }}</td>
                    <td>
                        @if($category->is_active)
                        <span class="badge badge-success badge-sm badge-soft">Đang dùng</span>
                        @else
                        <span class="badge badge-ghost badge-sm">Ngừng dùng</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-1">
                            @can('update', $category)
                            <a href="{{ route('backend.categories.edit', $category) }}" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @endcan
                            @can('delete', $category)
                            <form method="POST" action="{{ route('backend.categories.destroy', $category) }}"
                                  onsubmit="return confirm('Xóa nhóm hàng \'{{ $category->name }}\'? Sản phẩm đang gán nhóm này sẽ chặn thao tác xóa.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-10 text-sm text-base-content/40">Chưa có nhóm hàng nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="text-xs text-base-content/40 mt-3">
    Mã nhóm (code) được dùng cứng trong logic hệ thống để tự động yêu cầu hồ sơ pháp lý tương ứng —
    chỉ System Admin được phép thêm mới hoặc đổi mã.
</p>

@endsection
