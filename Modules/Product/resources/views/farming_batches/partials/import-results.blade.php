@if($importResults = session('log_import_results'))
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body p-4 space-y-3">
        <h2 class="font-semibold text-sm">Kết quả import nhật ký ({{ count($importResults) }} sheet)</h2>
        @foreach($importResults as $result)
        <div class="rounded-lg border border-base-200 p-3 space-y-2">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="font-medium">📄 {{ $result['sheet'] }}</span>
                <span class="text-base-content/40">→</span>
                <a href="{{ $result['batch_url'] }}" class="font-mono font-semibold link link-primary">{{ $result['batch_code'] }}</a>
            </div>
            @if($result['created'] > 0)
            <div role="alert" class="alert alert-success py-2 px-3 text-sm">
                <span>✅ Đã import thành công <strong>{{ $result['created'] }}</strong> dòng dữ liệu.</span>
            </div>
            @endif
            @if($result['duplicates'] > 0)
            <div role="alert" class="alert alert-info alert-soft py-2 px-3 text-sm">
                <span>ℹ️ Đã bỏ qua <strong>{{ $result['duplicates'] }}</strong> dòng bị trùng lặp dữ liệu (đã có trong nhật ký).</span>
            </div>
            @endif
            @if($result['warnings'])
            <div role="alert" class="alert alert-warning alert-soft py-2 px-3 text-sm items-start">
                <div>
                    <p class="font-semibold mb-1">⚠️ Cảnh báo VietGAP ({{ count($result['warnings']) }})</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($result['warnings'] as $msg)<li class="font-medium">{{ $msg }}</li>@endforeach
                    </ul>
                </div>
            </div>
            @endif
            @if($result['errors'])
            <div role="alert" class="alert alert-error alert-soft py-2 px-3 text-sm items-start">
                <div>
                    <p class="font-semibold mb-1">❌ Dòng không import được ({{ count($result['errors']) }})</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($result['errors'] as $msg)<li>{{ $msg }}</li>@endforeach
                    </ul>
                </div>
            </div>
            @endif
            @if($result['created'] === 0 && $result['duplicates'] === 0 && !$result['errors'])
            <div role="alert" class="alert alert-info alert-soft py-2 px-3 text-sm">
                <span>Không tìm thấy dòng nhật ký nào trong sheet này.</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
