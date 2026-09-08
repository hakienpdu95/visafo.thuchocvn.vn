@extends('layouts.mobile')
@section('title', 'Không tìm thấy')
@section('subtitle', 'Cổng truy xuất nguồn gốc')

@section('content')
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body items-center text-center py-10">
        <svg class="w-12 h-12 text-warning mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        <h1 class="font-semibold mb-1">Không tìm thấy mã sản phẩm này</h1>
        <p class="text-sm text-base-content/60">Mã bạn quét không tồn tại trong hệ thống truy xuất. Đây có thể là dấu hiệu hàng giả — vui lòng liên hệ nơi mua hàng để xác minh.</p>
    </div>
</div>
@endsection
