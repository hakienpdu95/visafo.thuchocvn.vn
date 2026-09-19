{{--
    Partial tem mặc định 60x40 mm. Được nhúng bởi labels.master_print (không có <html>/<body>).
    Biến: $item (->product->name, ->salesOrder->customer_name/delivery_address),
          $log (->weight_per_label, ->mfg_date, ->exp_date, ->supplier_name),
          $attributes (thông tin bổ sung EAV), $qrSvg (tuỳ chọn).
--}}
@once
<style>
    .tpl-general .label { width: 60mm; height: 40mm; padding: 2mm 2.5mm; display: flex; gap: 2mm; overflow: hidden; background: #fff; }
    .tpl-general .info { flex: 1; min-width: 0; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
    .tpl-general .info > * { flex: none; }
    .tpl-general .name { font-size: 8.5pt; font-weight: 700; line-height: 1.15; max-height: 2.3em; overflow: hidden; margin-bottom: .8mm; }

    .tpl-general .dynamic-info-container { flex: 1 1 auto; min-height: 0; max-height: 18mm; overflow: hidden; font-size: 9px; line-height: 1.2; margin-bottom: 2px; color: #222; }
    .tpl-general .dynamic-info-container b { font-weight: 700; }
    .tpl-general .data-row { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
    .tpl-general .data-row.single { -webkit-line-clamp: 1; }

    .tpl-general .weight { font-size: 13pt; font-weight: 700; line-height: 1; }
    .tpl-general .dates { display: flex; gap: 1.5mm; font-size: 6.3pt; line-height: 1.3; margin-top: .8mm; white-space: nowrap; }
    .tpl-general .dates b { font-weight: 700; }
    .tpl-general .supplier { font-size: 6pt; margin-top: .4mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .tpl-general .qr { width: 15mm; flex: none; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
    .tpl-general .trace-code { text-align: center; font-family: 'Courier New', monospace; font-size: 5pt; font-weight: 700; line-height: 1; margin-top: .5mm; }
    .tpl-general .qr svg { width: 15mm; height: 15mm; display: block; }

    @media screen { .tpl-general .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
<div class="label-wrapper tpl-general">
    <div class="label">
        <div class="info">
            <div class="name">{{ $item->product->name }}</div>
            <div class="dynamic-info-container">
                @foreach($attributes as $attr)
                    <div class="data-row"><b>{{ $attr->attribute_key }}:</b> {{ $attr->attribute_value }}</div>
                @endforeach
                <div class="data-row single"><b>KH:</b> {{ $item->salesOrder->customer_name ?? '—' }}</div>
                <div class="data-row single"><b>Giao:</b> {{ $item->salesOrder->delivery_address ?? '—' }}</div>
            </div>
            <div class="weight">{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) }} kg</div>
            <div class="dates">
                <span><b>NSX:</b> {{ $log->mfg_date?->format('d/m/Y') ?? '—' }}</span>
                <span><b>HSD:</b> {{ $log->exp_date?->format('d/m/Y') ?? '—' }}</span>
            </div>
            @if(!empty($log->supplier_name))
            <div class="supplier"><b>Nguồn:</b> {{ $log->supplier_name }}</div>
            @endif
        </div>
        @isset($qrSvg)
        <div class="qr">
            {!! $qrSvg !!}
            @if(!empty($log->trace_code))<div class="trace-code">{{ strtoupper($log->trace_code) }}</div>@endif
        </div>
        @endisset
    </div>
</div>
