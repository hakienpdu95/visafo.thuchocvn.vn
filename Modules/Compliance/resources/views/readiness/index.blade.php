@extends('layouts.backend')
@section('title', 'Kiểm tra Readiness')

@php
    $circumference = 2 * M_PI * 54;
    $percent = $score->percent();
@endphp

@section('content')

<div class="mb-6">
    <p class="text-sm text-base-content/50 mt-0.5">Điểm sẵn sàng chào hàng — tổng hợp chéo hồ sơ nội bộ, nhà cung cấp, nhật ký sản xuất và nhân sự</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    <div class="lg:col-span-2 rounded-2xl bg-green-800 text-white p-6 flex flex-col sm:flex-row items-center gap-6">
        <div class="relative shrink-0" style="width:140px;height:140px;">
            <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="10"/>
                <circle cx="60" cy="60" r="54" fill="none" stroke="white" stroke-width="10" stroke-linecap="round"
                        stroke-dasharray="{{ $circumference }}"
                        stroke-dashoffset="{{ $circumference * (1 - $percent / 100) }}"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold">{{ $score->totalScore }}</span>
                <span class="text-xs text-white/70">/ {{ $score->maxScore }} điểm</span>
            </div>
        </div>
        <div class="text-center sm:text-left">
            <div class="text-lg font-semibold">{{ $score->verdictLabel() }}</div>
            <p class="text-sm text-white/70 mt-1">Điểm sẵn sàng được cộng dồn từ 5 nhóm tiêu chí: Pháp lý doanh nghiệp, An toàn thực phẩm, Nguồn cung &amp; Truy xuất, Nhân sự &amp; sức khỏe, Năng lực thương mại.</p>
            <div class="flex flex-wrap gap-2 mt-3">
                @foreach($score->categories as $category)
                <span class="badge badge-sm bg-white/10 border-white/20 text-white">{{ $category->label }} {{ $category->score }}/{{ $category->maxScore }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-base-100 border border-base-200 shadow-sm p-5">
        <h2 class="font-semibold text-base-content mb-3">5 hồ sơ cần xử lý</h2>
        @if(empty($score->topIssues))
        <p class="text-sm text-base-content/50">Không có điểm nghẽn — hồ sơ đã sẵn sàng chào hàng.</p>
        @else
        <ul class="space-y-3">
            @foreach($score->topIssues as $issue)
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 text-error shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-base-content leading-snug">{{ $issue['label'] }}</p>
                    <p class="text-xs text-base-content/50">{{ $issue['category'] }}@if($issue['note']) — {{ $issue['note'] }}@endif</p>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </div>

</div>

<div class="space-y-3 mb-8">
    @foreach($score->categories as $category)
    <details class="rounded-xl bg-base-100 border border-base-200 shadow-sm group" {{ $category->score === 0 ? 'open' : '' }}>
        <summary class="cursor-pointer list-none p-4 flex items-center gap-4">
            <svg class="w-4 h-4 text-base-content/40 shrink-0 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            <span class="font-semibold text-base-content flex-1">{{ $category->label }}</span>
            <div class="w-40 hidden sm:block">
                <div class="h-2 rounded-full bg-base-200 overflow-hidden">
                    <div class="h-full rounded-full {{ $category->percent() >= 100 ? 'bg-success' : ($category->percent() >= 50 ? 'bg-warning' : 'bg-error') }}" style="width: {{ $category->percent() }}%"></div>
                </div>
            </div>
            <span class="text-sm font-medium text-base-content/70 w-16 text-right">{{ $category->score }}/{{ $category->maxScore }}</span>
        </summary>
        <div class="border-t border-base-200 p-4 space-y-2.5">
            @foreach($category->items as $item)
            <div class="flex items-start gap-2.5">
                @if($item->passed)
                <svg class="w-4 h-4 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.5 12.75l6 6 9-13.5"/></svg>
                @else
                <span class="w-4 h-4 rounded-full border-2 border-base-300 shrink-0 mt-0.5"></span>
                @endif
                <div class="min-w-0">
                    <p class="text-sm text-base-content">{{ $item->label }}</p>
                    @if($item->note)
                    <p class="text-xs text-base-content/50">{{ $item->note }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </details>
    @endforeach
</div>

@endsection
