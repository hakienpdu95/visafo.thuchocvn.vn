@extends('layouts.backend')
@section('title', 'Phòng ban / Bộ phận')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Phòng ban / Bộ phận</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Sơ đồ tổ chức — đánh dấu phòng ban trực tiếp tiếp xúc thực phẩm để kiểm soát hồ sơ y tế gắt gao hơn</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm">Danh sách nhân viên</a>
        @can('create', \Modules\Employee\Models\Department::class)
        <a href="{{ route('backend.departments.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm phòng ban
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0 overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên phòng ban</th>
                    <th class="text-center">Tiếp xúc thực phẩm</th>
                    <th class="text-center">Số nhân viên</th>
                    <th class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $department)
                <tr>
                    <td class="font-medium">{{ $department->name }}</td>
                    <td class="text-center">
                        @if($department->is_food_contact)
                        <span class="badge badge-warning badge-sm">Có</span>
                        @else
                        <span class="badge badge-ghost badge-sm">Không</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $department->employees_count }}</td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            @can('update', $department)
                            <a href="{{ route('backend.departments.edit', $department) }}" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @endcan
                            @can('delete', $department)
                            <form method="POST" action="{{ route('backend.departments.destroy', $department) }}" onsubmit="return confirm('Xóa phòng ban &quot;{{ $department->name }}&quot;?')">
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
                    <td colspan="4" class="text-center py-10 text-base-content/40 text-sm">Chưa có phòng ban nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
