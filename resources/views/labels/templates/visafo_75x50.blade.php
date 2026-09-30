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
    .tpl-visafo75.label-wrapper { font-family: Arial, Helvetica, "Segoe UI", sans-serif !important; }
    .tpl-visafo75.label-wrapper * { font-family: inherit !important; -webkit-font-smoothing: none; -moz-osx-font-smoothing: grayscale; }
    /* Khổ 75x50mm — bản thu gọn của visafo_100x75. Mọi khối đều overflow: hidden + chiều cao chặn trên,
       riêng .info-qr-row là khối co giãn duy nhất → dù dữ liệu dài đến đâu tổng chiều cao vẫn đúng 50mm. */
    .tpl-visafo75.label-wrapper { width: 75mm; height: 50mm; overflow: hidden; }
    .tpl-visafo75 .label {
        width: 75mm; height: 50mm; box-sizing: border-box; overflow: hidden; position: relative;
        background: #fff; color: #000; border: 1px solid #000; border-radius: 4px; padding: 0.5mm 0.9mm 0.5mm 1mm;
        display: flex; flex-direction: column; justify-content: space-between;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }

    /* A. Header: tên công ty (trái) + logo (phải) — tối đa 6mm */
    .tpl-visafo75 .header { flex: none; overflow: hidden; display: flex; align-items: center; gap: 1mm; justify-content: space-between;}
    .tpl-visafo75 .co-name { flex: 1; min-width: 0; font-size: 7pt; font-weight: bold; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tpl-visafo75 .co-slogan { flex: 1; min-width: 0; font-size: 7pt; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    /* Logo gốc gần vuông → chặn theo chiều cao header, bề ngang tối đa 12mm */
    .tpl-visafo75 .logo { flex: none; max-width: 10mm; max-height: 5.5mm; object-fit: contain; }

    /* B. Khối sản phẩm — 1 cột, tên tối đa 2 dòng. Cao ~10.7mm (2 dòng 12pt x 1.2 + viền): line-height thấp hơn sẽ làm dấu chữ hoa tiếng Việt của dòng thứ 3 lọt vào */
    .tpl-visafo75 .product-block { flex: none; overflow: hidden; border: 1px solid #000; padding: 0.3mm 1mm 0mm; box-sizing: border-box; }
    .tpl-visafo75 .product-name {
        font-size: 9.5pt; font-weight: 700; line-height: 1.2; letter-spacing: -.5px; text-transform: uppercase;
        overflow: hidden; overflow-wrap: anywhere;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    }
    .tpl-visafo75 .product-name .label-sm { font-size: 7pt; font-weight: 700; letter-spacing: 0; vertical-align: middle; }

    /* C. Thông tin (75%) + QR (25%) — khối co giãn duy nhất */
    .tpl-visafo75 .info-qr-row { flex: 1; min-height: 0; overflow: hidden; display: flex; align-items: flex-start; margin-top: .4mm;}
    .tpl-visafo75 .info-col { flex: none; width: 80%; min-width: 0; overflow: hidden; }
    .tpl-visafo75 .info-table { table-layout: fixed; width: 100%; border-collapse: collapse; font-size: 6.5pt; line-height: 1.1; }
    .tpl-visafo75 .info-table col.col-label { width: 12.5mm; }
    .tpl-visafo75 .info-table col.col-colon { width: 1.8mm; }
    .tpl-visafo75 .info-table td { padding: .1mm 0; vertical-align: top; overflow: hidden; }
    .tpl-visafo75 .info-table td.lbl { white-space: nowrap; font-weight: 500; font-size: 6pt; padding-right: 0.5mm;}
    .tpl-visafo75 .info-table td.colon { text-align: center; padding-right: 1mm;}
    .tpl-visafo75 .info-table td.val {font-weight: 600;}
    .tpl-visafo75 .one-line { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tpl-visafo75 .one-line.kl { font-size: 12pt }
    .tpl-visafo75 .two-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; line-height: 1.25; text-transform: uppercase;}
    .tpl-visafo75 .info-table td.strong { font-weight: 700; }
    .tpl-visafo75 .inline-lbl { font-weight: 700; font-size: 6pt; margin-left: 2mm; }

    .tpl-visafo75 .qr-col { flex: none; width: 20%; min-width: 0; overflow: hidden; display: flex; flex-direction: column; align-items: flex-end; gap: .4mm; }
    .tpl-visafo75 .qr-box { flex: none; width: 12mm; height: 12mm; padding: .6mm; box-sizing: border-box; display: flex; align-items: center; justify-content: center; }
    .tpl-visafo75 .qr-box svg { width: 100%; height: 100%; display: block; }
    .tpl-visafo75 .qr-box .qr-placeholder { font-size: 6pt; }
    .tpl-visafo75 .qr-caption { width: 13mm; font-size: 5pt; line-height: 1.1; text-align: center; overflow: hidden; }

    .tpl-visafo75 .instructions-block { flex: none; display: flex; overflow: hidden; max-height: 12.5mm; border-top: 1px solid #000; padding-top: .5mm; font-size: 5pt; line-height: 1.1; color: #000; }
    .tpl-visafo75 .instructions-block .ins-col { flex: 1 1 50%; min-width: 0; overflow: hidden; }
    .tpl-visafo75 .instructions-block .ins-col:first-child { border-right: 1px solid #000; padding-right: 1mm; }
    .tpl-visafo75 .instructions-block .ins-col:last-child { padding-left: 1mm; }
    .tpl-visafo75 .ins-title { display: flex; align-items: center; gap: .6mm; font-size: 5.5pt; font-weight: 700; text-transform: uppercase; margin-bottom: .2mm; }
    .tpl-visafo75 .ins-title svg { width: 2.8mm; height: 2.8mm; flex: none; }
    .tpl-visafo75 .ins-text { margin: 0; }
    .tpl-visafo75 .ins-list { margin: 0; padding-left: 2mm; list-style: disc; }

    /* D. Footer duy nhất — 1 dòng dưới đáy tem */
    .tpl-visafo75 .footer { flex: none; height: 3.6mm; box-sizing: border-box; overflow: hidden; display: flex; justify-content: center; align-items: center; gap: 1mm; border-top: 1px solid #000; padding-top: .5mm; font-size: 6pt; line-height: 1; white-space: nowrap; }
    .tpl-visafo75 .footer > div { display: flex; align-items: center; gap: .5mm; min-width: 0; overflow: hidden; }
    .tpl-visafo75 .footer svg { width: 2.6mm; height: 2.6mm; flex: none; }

    @media screen { .tpl-visafo75 .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
    @media print {
        .tpl-visafo75.label-wrapper, .tpl-visafo75 .label { width: 75mm; height: 50mm; max-height: 50mm; overflow: hidden; break-inside: avoid; page-break-inside: avoid; }
        .tpl-visafo75 .label { box-shadow: none; }
    }
</style>
@endonce
<div class="label-wrapper tpl-visafo75">
    <div class="label">
        {{-- A. Header --}}
        <div class="header">
            <div class="h-left">
                <div class="co-name">CÔNG TY CỔ PHẦN THỰC PHẨM VISAFO</div>
                <div class="co-slogan">Mang an toàn đến từng bữa ăn</div>
            </div>
            <img class="logo" src="{{ asset('images/visafo-dark.png') }}" alt="VISAFO">
        </div>

        {{-- B. Khối sản phẩm --}}
        <div class="product-block">
            <div class="product-name"><span class="label-sm">SẢN PHẨM:</span> {{ $item->product->name }}</div>
        </div>

        {{-- C. Thông tin chi tiết & QR --}}
        <div class="info-qr-row">
            <div class="info-col">
                <table class="info-table">
                    <colgroup>
                        <col class="col-label"><col class="col-colon"><col class="col-value">
                    </colgroup>
                    <tr>
                        <td class="lbl">SL/KL</td><td class="colon">:</td>
                        <td class="val strong"><div class="one-line kl">{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) }} {{ $unit }}</div></td>
                    </tr>
                    <tr>
                        <td class="lbl strong">NSX</td><td class="colon">:</td>
                        <td class="val strong"><div class="one-line">{{ $log->mfg_date?->format('d/m/Y') ?? '—' }}<span class="inline-lbl">HSD:</span> {{ $log->exp_date?->format('d/m/Y') ?? '—' }}</div></td>
                    </tr>
                    <tr>
                        <td class="lbl">Khách hàng</td><td class="colon">:</td>
                        <td class="val"><div class="two-lines">{{ $item->salesOrder->customer_name ?? '—' }}</div></td>
                    </tr>
                    <tr>
                        <td class="lbl">Điểm giao</td><td class="colon">:</td>
                        <td class="val"><div class="two-lines">{{ $item->salesOrder->delivery_address ?? '—' }}</div></td>
                    </tr>                    
                </table>
            </div>
            <div class="qr-col">
                <div class="qr-box">
                    @isset($qrSvg){!! $qrSvg !!}@else<div class="qr-placeholder">QR</div>@endisset
                </div>
                <div class="qr-caption">Quét QR để xem thông tin truy xuất</div>
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

        {{-- D. Footer: Liên hệ --}}
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
