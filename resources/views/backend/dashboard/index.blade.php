{{-- resources/views/backend/dashboard/index.blade.php --}}
@extends('layouts.backend')

@section('title', 'Dashboard')

@section('content')
@php
    /** @var \App\Models\User $authUser */
    $authUser  = auth()->user();
    $roleName  = $primary_role ?? 'viewer';
    $roleLabel = \App\Enums\RoleEnum::tryFrom($roleName)?->label() ?? ucfirst($roleName);
@endphp

{{-- ── Header ────────────────────────────────────────────────────────────── --}}
<div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-wide text-green-800">TỔNG QUAN HỒ SƠ</p>
        <h1 class="text-3xl font-serif text-base-content mb-2">Sẵn sàng hơn cho mỗi cơ hội cung ứng.</h1>
        <p class="text-gray-500">Theo dõi một luồng thống nhất từ hồ sơ doanh nghiệp đến gói chào hàng theo từng khách hàng.</p>
    </div>
    <a href="{{ route('backend.sales-packages.create') }}"
       class="bg-green-800 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-green-900 transition-colors flex items-center gap-2 shrink-0">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tạo gói chào hàng
    </a>
</div>

{{-- ── 4 Thẻ tổng quan ──────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

    {{-- Card 1: Điểm sẵn sàng --}}
    <a href="{{ route('backend.readiness-check.index') }}" class="rounded-md bg-green-800 text-white p-6 flex flex-col hover:opacity-95 transition-opacity">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-white/80">Điểm sẵn sàng</span>
            <svg class="w-5 h-5 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="text-4xl font-bold leading-none">{{ $readiness->totalScore }}<span class="text-lg text-white/60">/{{ $readiness->maxScore }}</span></div>
        <div class="w-full bg-white/20 rounded-full h-1.5 mt-4">
            <div class="bg-white h-1.5 rounded-full" style="width: {{ $readiness->percent() }}%"></div>
        </div>
        <p class="text-xs text-white/70 mt-3">Chưa có dữ liệu so sánh kỳ trước</p>
    </a>

    {{-- Card 2: Hồ sơ hợp lệ --}}
    <a href="{{ route('backend.document-repository.index') }}" class="card bg-base-100 border border-base-200 shadow-sm hover:border-primary/30 transition-colors">
        <div class="card-body p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-base-content/60">Hồ sơ hợp lệ</span>
                <div class="w-9 h-9 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-base-content leading-none">{{ $compliance_stats['percent'] }}%</div>
            <p class="text-xs text-base-content/50 mt-2">{{ $compliance_stats['valid'] }}/{{ $compliance_stats['total'] }} tài liệu đang hiệu lực</p>
        </div>
    </a>

    {{-- Card 3: Nguồn cung --}}
    <a href="{{ route('backend.vendors.index') }}" class="card bg-base-100 border border-base-200 shadow-sm hover:border-primary/30 transition-colors">
        <div class="card-body p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-base-content/60">Nguồn cung</span>
                <div class="w-9 h-9 rounded-xl bg-info/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-base-content leading-none">{{ $vendor_stats['active_count'] }}</div>
            <p class="text-xs text-base-content/50 mt-2">Nhà cung cấp đang hợp tác</p>
            @if($vendor_stats['missing_count'] > 0)
            <p class="text-xs text-warning mt-1 font-medium">{{ $vendor_stats['missing_count'] }} NCC cần bổ sung hồ sơ</p>
            @endif
        </div>
    </a>

    {{-- Card 4: Gói chào hàng --}}
    <a href="{{ route('backend.sales-packages.index') }}" class="card bg-base-100 border border-base-200 shadow-sm hover:border-primary/30 transition-colors">
        <div class="card-body p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-base-content/60">Gói chào hàng</span>
                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-base-content leading-none">{{ $package_stats['total'] }}</div>
            <p class="text-xs text-base-content/50 mt-2">Tổng số gói đã tạo</p>
            @if($package_stats['draft'] > 0)
            <p class="text-xs text-info mt-1 font-medium">{{ $package_stats['draft'] }} bản nháp đang chờ hoàn thiện</p>
            @endif
        </div>
    </a>

</div>

{{-- ── Mức độ hoàn thiện + Cần ưu tiên ──────────────────────────────────── --}}
@php
    $circumference = 2 * M_PI * 54;
    $percent = $readiness->percent();
@endphp
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">

    {{-- Cột trái: Mức độ hoàn thiện theo nhóm --}}
    <div class="lg:col-span-7 card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-6">
            <h2 class="font-semibold text-base-content mb-5">Mức độ hoàn thiện theo nhóm</h2>
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <div class="relative shrink-0" style="width:140px;height:140px;">
                    <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="currentColor" class="text-base-200" stroke-width="10"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="currentColor" class="{{ $readiness->verdictClass() }}" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $circumference * (1 - $percent / 100) }}"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold text-base-content">{{ $readiness->totalScore }}</span>
                        <span class="text-xs text-base-content/50">/ {{ $readiness->maxScore }} điểm</span>
                    </div>
                </div>

                <div class="w-full space-y-3">
                    @foreach($readiness->categories as $category)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-base-content/70">{{ $category->label }}</span>
                            <span class="text-base-content/40">{{ $category->score }}/{{ $category->maxScore }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-base-200 overflow-hidden">
                            <div class="h-full rounded-full {{ $category->percent() >= 100 ? 'bg-success' : ($category->percent() >= 50 ? 'bg-warning' : 'bg-error') }}" style="width: {{ $category->percent() }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Cột phải: Cần ưu tiên --}}
    <div class="lg:col-span-5 card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-6">
            <h2 class="font-semibold text-base-content mb-4">Cần ưu tiên</h2>

            @if(empty($priority_issues))
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <svg class="w-10 h-10 text-base-content/15 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-medium text-base-content/40">Không có điểm nghẽn</p>
            </div>
            @else
            <div class="space-y-2.5 mb-4">
                @foreach($priority_issues as $issue)
                <div class="flex items-start gap-3 bg-warning/5 border border-warning/15 rounded-xl px-3.5 py-3">
                    <div class="w-6 h-6 rounded-full bg-warning/15 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-warning font-bold text-sm leading-none">!</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-base-content leading-snug">{{ $issue['label'] }}</p>
                        <p class="text-xs text-base-content/50 mt-0.5">{{ $issue['category'] }}@if($issue['note']) — {{ $issue['note'] }}@endif</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <a href="{{ route('backend.readiness-check.index') }}" class="btn btn-ghost btn-sm w-full gap-1.5">
                Mở checklist Readiness
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>

</div>

{{-- ── Dòng hồ sơ gần đây ────────────────────────────────────────────────── --}}
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body p-0">
        <div class="px-6 py-4 border-b border-base-200">
            <h2 class="font-semibold text-base-content">Dòng hồ sơ gần đây</h2>
            <p class="text-xs text-base-content/40 mt-0.5">Cập nhật mới nhất từ Kho tài liệu và Gói chào hàng</p>
        </div>

        @if($recent_activity->isEmpty())
        <div class="flex flex-col items-center justify-center py-14 px-5 text-center">
            <svg class="w-12 h-12 text-base-content/15 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-sm font-medium text-base-content/40">Chưa có hồ sơ nào được cập nhật</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tên hồ sơ / gói</th>
                        <th>Đối tượng</th>
                        <th>Trạng thái</th>
                        <th class="text-right">Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recent_activity as $row)
                    <tr class="hover:bg-base-200/40">
                        <td>
                            <a href="{{ $row['url'] }}" class="font-medium text-sm hover:text-primary transition-colors">{{ $row['title'] }}</a>
                            <span class="badge badge-ghost badge-xs ml-1">{{ $row['type'] === 'package' ? 'Gói chào hàng' : 'Tài liệu' }}</span>
                        </td>
                        <td class="text-sm text-base-content/70">{{ $row['subject'] }}</td>
                        <td><span class="badge badge-sm badge-soft {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span></td>
                        <td class="text-right text-xs text-base-content/50">{{ $row['time_label'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection