@php
    $unit = ($item->unit_raw ?? null) ?: 'kg';
    $companyPhone   = '0972 402 619';
    $companyWebsite = 'www.visafo.com.vn';
    $storageText    = 'Giữ sản phẩm ở nhiệt độ từ 4-10 độ C. Đối với củ quả phải giữ sản phẩm ở nhiệt độ mát, phải tránh ánh nắng trực tiếp.';
    $usageSteps     = [
        'Sơ chế, loại bỏ phần già, lá úa.',
        'Rửa ít nhất 03 lần với nước sạch, để ráo.',
        'Chế biến thành món ăn theo nhu cầu.',
    ];
@endphp
@once
<style>
    .tpl-visafo-rcq80.label-wrapper { font-family: Arial, Roboto, Helvetica, sans-serif !important; width: 80mm; height: 60mm; overflow: hidden; }
    .tpl-visafo-rcq80.label-wrapper * { font-family: inherit !important; color: #000; -webkit-font-smoothing: none; -moz-osx-font-smoothing: grayscale; }
    .tpl-visafo-rcq80 .label {
        width: 80mm; height: 60mm; box-sizing: border-box; overflow: hidden; position: relative;
        background: #fff; color: #000; border: 1px solid #000; border-radius: 4px; padding: 0.4mm 1.2mm 1.6mm 1.2mm;
        display: flex; flex-direction: column; justify-content: space-between; gap: .4mm;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }

    .tpl-visafo-rcq80 .header { flex: none; overflow: hidden; display: flex; align-items: center; gap: 1mm; }
    .tpl-visafo-rcq80 .h-left { flex: 1; min-width: 0; overflow: hidden; border-bottom: 1px solid #000; padding-bottom: .3mm; }
    .tpl-visafo-rcq80 .h-left .co-big { font-size: 8.8pt; font-weight: bold; letter-spacing: 0; font-stretch: condensed; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.1; }
    .tpl-visafo-rcq80 .h-left .co-slogan { font-size: 6.5pt; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2; }
    .tpl-visafo-rcq80 .h-right { flex: none; display: flex; align-items: flex-end; }
    .tpl-visafo-rcq80 .h-right img { width: 10mm; max-height: 6.8mm; object-fit: contain; flex: none; }

    .tpl-visafo-rcq80 .product-block { flex: none; overflow: hidden; display: flex; align-items: center; border-radius: 3px; border: 1px solid #000; padding: 0.4mm 0.5mm 0mm 1mm; box-sizing: border-box; }
    .tpl-visafo-rcq80 .product-left { flex: 1; min-width: 0; overflow: hidden; }
    .tpl-visafo-rcq80 .product-left .label-sm { font-size: 7pt; font-weight: 700; line-height: 1.15; }
    .tpl-visafo-rcq80 .product-left .product-name { font-size: 10.5pt; font-weight: 700; letter-spacing: -.6px; text-transform: uppercase; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .tpl-visafo-rcq80 .info-qr-row { display: flex; flex: 1; min-height: 0; overflow: hidden; align-items: flex-start; }
    .tpl-visafo-rcq80 .info-col { flex: none; width: 78%; min-width: 0; overflow: hidden; box-sizing: border-box; }
    .tpl-visafo-rcq80 .info-table { table-layout: fixed; width: 100%; border-collapse: collapse; font-size: 6.8pt; line-height: 1.2; }
    .tpl-visafo-rcq80 .info-table col.col-label { width: 13.5mm; }
    .tpl-visafo-rcq80 .info-table col.col-colon { width: 2.2mm; }
    .tpl-visafo-rcq80 .info-table col.col-value { width: auto; }
    .tpl-visafo-rcq80 .info-table td { padding: .28mm 0; vertical-align: top; }
    .tpl-visafo-rcq80 .info-table td.lbl { font-weight: 500; white-space: nowrap; padding-right: 0.5mm; font-size: 6.5pt; }
    .tpl-visafo-rcq80 .info-table td.lbl.strong { font-weight: 700; }
    .tpl-visafo-rcq80 .info-table td.colon { text-align: center; padding-right: 1.2mm; }
    .tpl-visafo-rcq80 .info-table td.val { word-wrap: break-word; overflow-wrap: break-word; }
    .tpl-visafo-rcq80 .info-table tr.weight td { font-weight: 500; }
    .tpl-visafo-rcq80 .info-table tr.weight td.val { font-weight: 600; font-size: 10pt; }
    .tpl-visafo-rcq80 .info-table tr.batch td { font-weight: 500; }
    .tpl-visafo-rcq80 .info-table tr.batch td.val { font-weight: 600; font-size: 6.5pt; }
    .tpl-visafo-rcq80 .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.16; text-transform: uppercase; }

    .tpl-visafo-rcq80 .qr-col { flex: none; width: 22%; min-width: 0; overflow: hidden; display: flex; flex-direction: column; align-items: flex-end; justify-content: center; text-align: center; box-sizing: border-box; }
    .tpl-visafo-rcq80 .qr-box { flex: none; border: 1px solid #000; padding: 0.5mm; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 96%; border-radius: 3px; }
    .tpl-visafo-rcq80 .qr-box svg { width: 100%; height: auto; aspect-ratio: 1 / 1; display: block; }
    .tpl-visafo-rcq80 .qr-box .qr-placeholder { font-size: 6.5pt; }
    .tpl-visafo-rcq80 .qr-box .qr-caption { flex: none; font-size: 5pt; line-height: 1.1; padding-top: 0.4mm; }

    .tpl-visafo-rcq80 .instructions-block { flex: none; display: flex; overflow: hidden; max-height: 12.5mm; border-top: 1px solid #000; padding-top: .5mm; font-size: 5pt; line-height: 1.1; color: #000; }
    .tpl-visafo-rcq80 .instructions-block .ins-col { flex: 1 1 50%; min-width: 0; overflow: hidden; }
    .tpl-visafo-rcq80 .instructions-block .ins-col:first-child { border-right: 1px solid #000; padding-right: 1mm; }
    .tpl-visafo-rcq80 .instructions-block .ins-col:last-child { padding-left: 1mm; }
    .tpl-visafo-rcq80 .ins-title { display: flex; align-items: center; gap: .6mm; font-size: 5.5pt; font-weight: 700; text-transform: uppercase; margin-bottom: .2mm; }
    .tpl-visafo-rcq80 .ins-title svg { width: 2.8mm; height: 2.8mm; flex: none; }
    .tpl-visafo-rcq80 .ins-text { margin: 0; }
    .tpl-visafo-rcq80 .ins-list { margin: 0; padding-left: 2mm; list-style: disc; }

    .tpl-visafo-rcq80 .footer { flex: none; height: 4.2mm; box-sizing: border-box; overflow: hidden; display: flex; justify-content: center; align-items: center; gap: 1mm; border-top: 1px solid #000; padding-top: .6mm; font-size: 7pt; line-height: 1; white-space: nowrap; }
    .tpl-visafo-rcq80 .footer > div { display: flex; align-items: center; gap: .6mm; min-width: 0; overflow: hidden; }
    .tpl-visafo-rcq80 .footer svg { width: 3mm; height: 3mm; flex: none; }

    @media screen { .tpl-visafo-rcq80 .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
    @media print {
        .tpl-visafo-rcq80.label-wrapper, .tpl-visafo-rcq80 .label { width: 80mm; height: 60mm; max-height: 60mm; overflow: hidden; break-inside: avoid; page-break-inside: avoid; }
        .tpl-visafo-rcq80 .label { box-shadow: none; }
    }
</style>
@endonce
<div class="label-wrapper tpl-visafo-rcq80">
    <div class="label">
        <div class="header">
            <div class="h-left">
                <div class="co-big">CÔNG TY CỔ PHẦN THỰC PHẨM VISAFO</div>
                <div class="co-slogan">Mang an toàn đến từng bữa ăn</div>
            </div>
            <div class="h-right">
                <img class="logo" src="{{ asset('images/visafo-dark.png') }}" alt="VISAFO">
            </div>
        </div>

        <div class="product-block">
            <div class="product-left">
                <div class="label-sm">SẢN PHẨM:</div>
                <div class="product-name">{{ $item->product->name }}</div>
            </div>
        </div>

        <div class="info-qr-row">
            <div class="info-col">
                <table class="info-table">
                    <colgroup>
                        <col class="col-label"><col class="col-colon"><col class="col-value">
                    </colgroup>
                    <tr class="weight">
                        <td class="lbl">SL/KL</td><td class="colon">:</td>
                        <td class="val">{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) }} {{ $unit }}</td>
                    </tr>
                    <tr>
                        <td class="lbl strong">NSX</td><td class="colon">:</td>
                        <td class="val">{{ $log->mfg_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl strong">HSD</td><td class="colon">:</td>
                        <td class="val">{{ $log->exp_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr class="batch">
                        <td class="lbl">Mã lô</td><td class="colon">:</td>
                        <td class="val">{{ $log->batch_code ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Khách hàng</td><td class="colon">:</td>
                        <td class="val"><div class="truncate-2-lines">{{ $item->salesOrder->customer_name ?? '—' }}</div></td>
                    </tr>
                    <tr>
                        <td class="lbl">Điểm giao</td><td class="colon">:</td>
                        <td class="val"><div class="truncate-2-lines">{{ $item->salesOrder->delivery_address ?? '—' }}</div></td>
                    </tr>
                </table>
            </div>
            <div class="qr-col">
                <div class="qr-box">
                    @isset($qrSvg){!! $qrSvg !!}@else<div class="qr-placeholder">QR</div>@endisset
                    <div class="qr-caption">Quét QR để xem thông tin truy xuất</div>
                </div>
            </div>
        </div>

        <div class="instructions-block">
            <div class="ins-col">
                <div class="ins-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="2" x2="12" y2="22"/><line x1="3.3" y1="7" x2="20.7" y2="17"/><line x1="3.3" y1="17" x2="20.7" y2="7"/>
                        <polyline points="9,3.5 12,6 15,3.5"/><polyline points="9,20.5 12,18 15,20.5"/>
                        <polyline points="3.5,10.5 6.2,8.5 5.5,5"/><polyline points="20.5,13.5 17.8,15.5 18.5,19"/>
                        <polyline points="3.5,13.5 6.2,15.5 5.5,19"/><polyline points="20.5,10.5 17.8,8.5 18.5,5"/>
                    </svg>
                    <span>Bảo quản:</span>
                </div>
                <p class="ins-text">{{ $storageText }}</p>
            </div>
            <div class="ins-col">
                <div class="ins-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/>
                        <line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/>
                    </svg>
                    <span>Hướng dẫn sử dụng:</span>
                </div>
                <ul class="ins-list">
                    @foreach($usageSteps as $step)
                    <li>{{ $step }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="footer">
            <div>
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 4h4l2 5-2.5 2A12 12 0 0 0 13.5 16.5L15.5 14l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 2 6a2 2 0 0 1 2-2z"/>
                </svg>
                <span>{{ $companyPhone }}</span>
                <span>|</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <ellipse cx="12" cy="12" rx="4" ry="9"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                </svg>
                <span>{{ $companyWebsite }}</span>
            </div>
        </div>
    </div>
</div>
