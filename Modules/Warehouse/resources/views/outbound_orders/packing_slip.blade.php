<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Phiếu xuất kho {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 0; padding: 12mm; font-size: 10pt; color: #1a1a1a; }
        h1 { font-size: 16pt; margin: 0 0 2mm; }
        .sub { color: #555; font-size: 9pt; margin-bottom: 6mm; }
        .info { display: flex; justify-content: space-between; margin-bottom: 6mm; }
        .info div { width: 48%; }
        .info p { margin: 0 0 1mm; }
        .label { color: #777; font-size: 8pt; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 4mm; }
        th, td { border: 1px solid #ccc; padding: 2.5mm; text-align: left; font-size: 9pt; vertical-align: top; }
        th { background: #f2f2f2; }
        .warn { color: #a15c00; }
        .footer { margin-top: 8mm; font-size: 8pt; color: #777; }
    </style>
</head>
<body>
    <h1>Phiếu xuất kho / Packing Slip</h1>
    <p class="sub">Mã đơn: <strong>{{ $order->order_number }}</strong> · Lập ngày {{ $order->ordered_at->format('d/m/Y') }} · Trạng thái: {{ $order->status->label() }}</p>

    <div class="info">
        <div>
            <p class="label">Đại lý nhận hàng</p>
            <p><strong>{{ $order->dealer_name }}</strong></p>
            <p>{{ $order->dealer_phone ?: '—' }}</p>
            <p>{{ $order->dealer_address ?: '—' }}</p>
        </div>
        <div>
            <p class="label">Tổng số lượng xuất</p>
            <p><strong>{{ $order->totalQuantity() }}</strong> đơn vị</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Mã lô</th>
                <th>HSD</th>
                <th>SL</th>
                <th>Dải Serial GS1</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->pickedBatches as $line)
            @php($allocation = $allocations[$line->id] ?? ['segments' => [], 'assigned_count' => 0])
            <tr>
                <td>{{ $line->product->name }}</td>
                <td>{{ $line->batch->internal_batch_code }}</td>
                <td>{{ $line->batch->exp_date->format('d/m/Y') }}</td>
                <td>{{ $line->quantity }}</td>
                <td>
                    @if($allocation['assigned_count'] < $line->quantity)
                    <span class="warn">Chưa quét mã xuất kho đủ số lượng</span>
                    @else
                    @foreach($allocation['segments'] as $segment)
                    <div>{{ $segment['label'] ?? 'Không seri hóa' }} ({{ $segment['count'] }})</div>
                    @endforeach
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5">Chưa có lô hàng nào được chọn.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($order->notes)
    <p class="footer"><strong>Ghi chú:</strong> {{ $order->notes }}</p>
    @endif

    <p class="footer">In lúc {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
