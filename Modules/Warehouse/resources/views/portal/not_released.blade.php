@extends('layouts.mobile')
@section('title', 'Chưa lưu hành')
@section('subtitle', 'Cổng truy xuất nguồn gốc')

@section('content')
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body items-center text-center py-10">
        <svg class="w-12 h-12 text-warning mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
        <h1 class="font-semibold mb-1">Sản phẩm chính hãng nhưng chưa được xuất bán chính thức từ nhà phân phối</h1>
        <p class="text-sm text-base-content/60">Sản phẩm này đã được ghi nhận trong hệ thống nhưng chưa đến thời điểm mở bán chính thức. Vui lòng liên hệ nhà phân phối để biết thêm chi tiết.</p>
    </div>
</div>
@endsection
