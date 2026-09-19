<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Tem {{ $order->misa_ref_id }}</title>
    <style>
        @page { size: 60mm 40mm; margin: 0; }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { background: #fff; color: #000; font-family: Arial, Helvetica, sans-serif; }

        .label {
            width: 60mm; height: 40mm; padding: 2mm 2.5mm;
            display: flex; gap: 2mm; overflow: hidden;
            page-break-after: always; break-after: page;
        }
        .label:last-of-type { page-break-after: auto; break-after: auto; }

        .info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .name { font-size: 9.5pt; font-weight: 700; line-height: 1.15; max-height: 2.4em; overflow: hidden; }
        .meta { font-size: 6.5pt; line-height: 1.25; margin-top: 1mm; color: #222; }
        .meta b { font-weight: 700; }
        .weight { font-size: 14pt; font-weight: 700; margin-top: auto; line-height: 1; }
        .dates { font-size: 7.5pt; line-height: 1.3; margin-top: 1mm; }
        .dates b { font-weight: 700; }
        .supplier { font-size: 6.5pt; margin-top: .5mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .qr { width: 17mm; flex: none; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
        .qr svg { width: 17mm; height: 17mm; display: block; }
        .qr small { font-size: 5pt; margin-top: .5mm; text-align: center; }

        @media screen {
            body { background: #e5e7eb; padding: 8mm; }
            .label { background: #fff; margin: 0 auto 6mm; box-shadow: 0 1px 4px rgba(0,0,0,.25); }
        }
    </style>
</head>
<body>
@foreach($logs as $log)
@php
    $item = $log->item;
    $weight = str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.'));
@endphp
@for($i = 0; $i < $log->label_count; $i++)
    <div class="label">
        <div class="info">
            <div class="name">{{ $item->product?->name ?? $item->product_name_raw }}</div>
            <div class="meta">
                <div><b>KH:</b> {{ $order->customer_name ?? '—' }}</div>
                <div><b>Giao:</b> {{ $order->delivery_address ?? '—' }}</div>
            </div>
            <div class="weight">{{ $weight }} kg</div>
            <div class="dates">
                <div><b>NSX:</b> {{ $log->mfg_date?->format('d/m/Y') ?? '—' }}</div>
                <div><b>HSD:</b> {{ $log->exp_date->format('d/m/Y') }}</div>
            </div>
            @if($log->supplier_name)
            <div class="supplier"><b>Nguồn:</b> {{ $log->supplier_name }}</div>
            @endif
        </div>
        <div class="qr">
            {!! $qrSvg !!}
            <small>{{ $order->misa_ref_id }}</small>
        </div>
    </div>
@endfor
@endforeach

<script>window.onload = function() { window.print(); window.close(); }</script>
</body>
</html>
