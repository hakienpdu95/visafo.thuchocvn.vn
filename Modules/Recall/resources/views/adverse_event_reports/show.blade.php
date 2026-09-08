@extends('layouts.backend')
@section('title', 'Khiếu nại — ' . $report->consumer_name)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            {{ $report->product->name }}
            <span class="badge {{ $report->status->badgeClass() }} badge-sm">{{ $report->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Người sử dụng: {{ $report->consumer_name }} · Nhận khiếu nại {{ $report->received_at->format('d/m/Y') }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.adverse-event-reports.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
        <a href="{{ route('backend.adverse-event-reports.pdf', $report) }}" class="btn btn-primary btn-sm">Xuất PDF (Phụ lục 18-MP)</a>
        @can('update', $report)
        @if($report->status->value === 'draft')
        <form method="POST" action="{{ route('backend.adverse-event-reports.submit', $report) }}" onsubmit="return confirm('Xác nhận đã nộp báo cáo này cho Cục Quản lý Dược?');">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">Đánh dấu đã nộp</button>
        </form>
        @elseif($report->status->value === 'submitted')
        <form method="POST" action="{{ route('backend.adverse-event-reports.close', $report) }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">Đóng báo cáo</button>
        </form>
        @endif
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2.5 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

@if($report->status->value === 'draft')
<div class="alert {{ $report->preliminaryDeadline()->isPast() ? 'alert-error' : 'alert-warning' }} py-2.5 px-4 mb-5 text-sm">
    Hạn báo cáo sơ bộ (7 ngày): <strong>{{ $report->preliminaryDeadline()->format('d/m/Y') }}</strong> ·
    Hạn báo cáo chi tiết (thêm 8 ngày): <strong>{{ $report->detailedDeadline()->format('d/m/Y') }}</strong>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-3">I. Thông tin công ty</h2>
            <dl class="text-sm space-y-2">
                <div><dt class="text-base-content/50 text-xs">Công ty</dt><dd>{{ $report->company_name }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Người thông báo</dt><dd>{{ $report->reporter_name }} {{ $report->reporter_title ? '('.$report->reporter_title.')' : '' }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Liên hệ</dt><dd>{{ $report->reporter_phone ?? '—' }} · {{ $report->reporter_email ?? '—' }}</dd></div>
            </dl>

            <h2 class="text-base font-semibold mt-5 mb-3">II. Thông tin sản phẩm</h2>
            <dl class="text-sm space-y-2">
                <div><dt class="text-base-content/50 text-xs">Sản phẩm</dt><dd>{{ $report->product->name }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Số lô</dt><dd class="font-mono">{{ $report->lotNumber() ?? '—' }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Xuất xứ/NSX</dt><dd>{{ $report->manufacturer_origin ?? '—' }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Ngày sản xuất/hạn dùng</dt><dd>{{ $report->mfgOrExpDateLabel() ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="text-base font-semibold mb-3">III. Chi tiết tác dụng bất lợi</h2>
            <dl class="text-sm space-y-2">
                <div><dt class="text-base-content/50 text-xs">Người sử dụng</dt><dd>{{ $report->consumer_name }} · {{ $report->consumer_age ?? '—' }} tuổi · {{ $report->consumer_gender?->label() ?? '—' }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Mô tả phản ứng</dt><dd class="whitespace-pre-line">{{ $report->reaction_description }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Nhập viện / Điều trị y tế</dt><dd>{{ $report->was_hospitalized ? 'Có' : 'Không' }} / {{ $report->required_medical_treatment ? 'Có' : 'Không' }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Kết quả</dt><dd>{{ $report->outcome?->label() ?? '—' }}</dd></div>
                <div><dt class="text-base-content/50 text-xs">Nguồn báo cáo</dt><dd>{{ $report->report_source?->label() ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>

</div>
@endsection
