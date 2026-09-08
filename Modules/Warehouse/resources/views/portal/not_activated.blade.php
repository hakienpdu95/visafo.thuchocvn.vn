@extends('layouts.mobile')
@section('title', 'Chưa kích hoạt')
@section('subtitle', 'Cổng truy xuất nguồn gốc')

@section('content')
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body items-center text-center py-10">
        <svg class="w-12 h-12 text-info mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        <h1 class="font-semibold mb-1">Tem chưa được kích hoạt</h1>
        <p class="text-sm text-base-content/60">Tem này đã được đăng ký trong hệ thống nhưng chưa được gắn kết với sản phẩm cụ thể nào. Vui lòng liên hệ nơi mua hàng để xác minh nguồn gốc sản phẩm.</p>
    </div>
</div>
@endsection
