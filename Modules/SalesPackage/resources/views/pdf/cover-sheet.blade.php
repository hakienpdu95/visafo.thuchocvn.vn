<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        .score-box { display: inline-block; padding: 10px 16px; border-radius: 8px; background: #166534; color: #fff; font-size: 20px; font-weight: bold; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background: #f3f4f6; }
        .group-title { margin-top: 16px; font-weight: bold; font-size: 13px; }
        .status-ok { color: #166534; font-weight: bold; }
        .status-bad { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <h1>MỤC LỤC HỒ SƠ CHÀO HÀNG</h1>
    <div class="meta">
        Khách hàng: <strong>{{ $package->customer->name }}</strong><br>
        Gói: <strong>{{ $package->name }}</strong> — Phiên bản V{{ $package->version }}<br>
        Ngày xuất: {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="score-box">Điểm sẵn sàng: {{ $package->readiness_score }}/100</div>

    @php $groups = $package->items->groupBy(fn ($item) => $item->groupLabel()); @endphp

    @foreach($groups as $groupLabel => $items)
        <div class="group-title">{{ $groupLabel }}</div>
        <table>
            <thead>
                <tr>
                    <th>Tên tài liệu</th>
                    <th>Số hiệu</th>
                    <th>Ngày cấp</th>
                    <th>Ngày hết hạn</th>
                    <th>Tình trạng</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->displayName() }}</td>
                    <td>{{ $item->is_custom ? '—' : ($item->document->document_number ?? '—') }}</td>
                    <td>{{ $item->is_custom ? '—' : (optional($item->document->issue_date)->format('d/m/Y') ?? '—') }}</td>
                    <td>{{ $item->is_custom ? '—' : (optional($item->document->expiration_date)->format('d/m/Y') ?? '—') }}</td>
                    <td class="{{ $item->is_valid ? 'status-ok' : 'status-bad' }}">{{ $item->is_valid ? 'Còn hiệu lực' : 'Cần lưu ý' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
