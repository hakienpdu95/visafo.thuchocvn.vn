@php
    $companyPhone   = '0972 402 619';
    $companyWebsite = 'www.visafo.com.vn';
@endphp
@once
<style>
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
    .tpl-visafo75 .header { flex: none; height: 6mm; max-height: 6mm; overflow: hidden; display: flex; align-items: center; gap: 1mm; }
    .tpl-visafo75 .co-name { flex: 1; min-width: 0; font-size: 7pt; font-weight: bold; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    /* Logo gốc gần vuông → chặn theo chiều cao header, bề ngang tối đa 12mm */
    .tpl-visafo75 .logo { flex: none; max-width: 12mm; max-height: 5.5mm; object-fit: contain; }

    /* B. Khối sản phẩm — 1 cột, tên tối đa 2 dòng. Cao ~10.7mm (2 dòng 12pt x 1.2 + viền): line-height thấp hơn sẽ làm dấu chữ hoa tiếng Việt của dòng thứ 3 lọt vào */
    .tpl-visafo75 .product-block { flex: none; overflow: hidden; border: 1px solid #000; padding: 0.5mm 1mm 0mm; box-sizing: border-box; }
    .tpl-visafo75 .product-name {
        font-size: 10.5pt; font-weight: 900; line-height: 1.2; letter-spacing: -.5px; text-transform: uppercase;
        overflow: hidden; overflow-wrap: anywhere;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    }
    .tpl-visafo75 .product-name .label-sm { font-size: 6pt; font-weight: 700; letter-spacing: 0; vertical-align: middle; }

    /* C. Thông tin (75%) + QR (25%) — khối co giãn duy nhất */
    .tpl-visafo75 .info-qr-row { flex: 1; min-height: 0; overflow: hidden; display: flex; align-items: flex-start; margin-top: 1mm;}
    .tpl-visafo75 .info-col { flex: none; width: 80%; min-width: 0; overflow: hidden; }
    .tpl-visafo75 .info-table { table-layout: auto; width: 100%; border-collapse: collapse; font-size: 6pt; line-height: 1.15; }
    .tpl-visafo75 .info-table col.col-label { width: 1%; }
    .tpl-visafo75 .info-table col.col-colon { width: 1%; }
    .tpl-visafo75 .info-table td { padding: .3mm 0; vertical-align: top; overflow: hidden; }
    .tpl-visafo75 .info-table td.lbl { white-space: nowrap; font-weight: 500; font-size: 5pt; padding-right: 0.5mm;}
    .tpl-visafo75 .info-table td.colon { text-align: center; padding-right: 1mm;}
    .tpl-visafo75 .one-line { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tpl-visafo75 .two-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; line-height: 1.2; }
    .tpl-visafo75 .info-table tr.weight td { vertical-align: middle; }
    .tpl-visafo75 .info-table tr.weight td.val { font-size: 7pt; font-weight: 700; }
    .tpl-visafo75 .info-table tr.sep td { border-top: 1px solid #000; padding-top: .4mm; }
    .tpl-visafo75 .info-table tr.batch td.val { font-weight: 700; }

    .tpl-visafo75 .qr-col { flex: none; width: 20%; min-width: 0; overflow: hidden; display: flex; flex-direction: column; align-items: flex-end; gap: .4mm; }
    .tpl-visafo75 .qr-box { flex: none; width: 13mm; height: 13mm; border: .5px solid #000; border-radius: 2px; padding: .4mm; box-sizing: border-box; display: flex; align-items: center; justify-content: center; }
    .tpl-visafo75 .qr-box svg { width: 100%; height: 100%; display: block; }
    .tpl-visafo75 .qr-box .qr-placeholder { font-size: 5pt; }
    .tpl-visafo75 .qr-caption { width: 12mm; font-size: 4pt; line-height: 1.4; text-align: center; overflow: hidden; }

    /* D. Footer duy nhất — 1 dòng dưới đáy tem */
    .tpl-visafo75 .footer { flex: none; height: 4mm; box-sizing: border-box; overflow: hidden; display: flex; justify-content: space-between; align-items: center; gap: 1mm; border-top: 1px solid #000; padding-top: 1mm; font-size: 5pt; line-height: 1; white-space: nowrap; }
    .tpl-visafo75 .footer > div { display: flex; align-items: center; gap: .5mm; min-width: 0; overflow: hidden; }
    .tpl-visafo75 .footer svg { width: 2.2mm; height: 2.2mm; flex: none; }

    @media screen { .tpl-visafo75 .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
<div class="label-wrapper tpl-visafo75">
    <div class="label">
        {{-- A. Header --}}
        <div class="header">
            <div class="co-name">CÔNG TY CỔ PHẦN THỰC PHẨM VISAFO</div>
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
                        <td class="lbl">Khách hàng</td><td class="colon">:</td>
                        <td class="val"><div class="two-lines">{{ $item->salesOrder->customer_name ?? '—' }}</div></td>
                    </tr>
                    <tr>
                        <td class="lbl">Điểm giao</td><td class="colon">:</td>
                        <td class="val"><div class="two-lines">{{ $item->salesOrder->delivery_address ?? '—' }}</div></td>
                    </tr>
                    <tr class="weight">
                        <td class="lbl">Khối lượng</td><td class="colon">:</td>
                        <td class="val"><div class="one-line">{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) }} kg</div></td>
                    </tr>
                    <tr class="sep">
                        <td class="lbl" style="font-weight: 700;">NSX</td><td class="colon">:</td>
                        <td class="val">
                            <div class="one-line">
                                {{ $log->mfg_date?->format('d/m/Y') ?? '—' }}&nbsp;&nbsp;<b>HSD:</b> {{ $log->exp_date?->format('d/m/Y') ?? '—' }}
                            </div>
                        </td>
                    </tr>
                    @if(!empty($log->supplier_name))
                    <tr>
                        <td class="lbl">Nguồn cung</td><td class="colon">:</td>
                        <td class="val"><div class="one-line">{{ $log->supplier_name }}</div></td>
                    </tr>
                    @endif
                    <tr class="batch">
                        <td class="lbl">Mã lô</td><td class="colon">:</td>
                        <td class="val"><div class="one-line">{{ $log->batch_code ?? '—' }}</div></td>
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

        {{-- D. Footer: Bảo quản | Liên hệ --}}
        <div class="footer">
            <div><span>Bảo quản: 4-10°C</span></div>
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
