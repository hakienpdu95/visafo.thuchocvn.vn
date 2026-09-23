@php
    $companyAddress = 'Số 120, Xóm Tây, Xã Phúc Thịnh, TP Hà Nội';
    $companyPhone   = '0972 402 619';
    $companyWebsite = 'www.visafo.com.vn';
@endphp
@once
<style>
    .tpl-visafo100.label-wrapper { font-family: Arial, Helvetica, "Segoe UI", sans-serif !important; }
    .tpl-visafo100.label-wrapper * { font-family: inherit !important; -webkit-font-smoothing: none; -moz-osx-font-smoothing: grayscale; }
    .tpl-visafo100 .label {
        width: 100mm; height: 75mm; box-sizing: border-box; overflow: hidden; position: relative;
        background: #fff; color: #000; border: 1px solid #000; border-radius: 5px; padding: 0.5mm 1mm 0.5mm 1.4mm;
        display: flex; flex-direction: column; justify-content: space-between; gap: 1.1mm;
    }

    /* A. Header: trái (tên công ty), giữa (tagline script), phải (logo) — cao tự nhiên theo nội dung, không ép cứng số mm */
    .tpl-visafo100 .header { flex: none; overflow: hidden; display: flex; align-items: center; gap: 1.2mm; }
    .tpl-visafo100 .h-left { flex: 1; min-width: 0; overflow: hidden; border-bottom: 1px solid #000; padding-bottom: .8mm; }
    .tpl-visafo100 .h-left .co-big { font-size: 11.5pt; font-weight: bold; letter-spacing: 0; font-stretch: condensed; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.1; }
    .tpl-visafo100 .h-left .co-slogan { font-size: 7pt; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4; margin-top: 0; }
    .tpl-visafo100 .h-right { flex: none; display: flex; align-items: flex-end; gap: .8mm; }
    .tpl-visafo100 .h-right img { width: 13mm; object-fit: contain; flex: none; }

    /* B. Khối sản phẩm — nền đen chữ trắng — cao tự nhiên theo nội dung; font vừa phải + line-clamp để không phình to */
    .tpl-visafo100 .product-block { flex: none; overflow: hidden; display: flex; align-items: center; color: #000; border-radius: 3px; border: 1px solid #000; padding: 1mm 0.5mm 0mm 1mm; gap: 2mm; box-sizing: border-box; }
    .tpl-visafo100 .product-left { flex: 1; min-width: 0; overflow: hidden; }
    .tpl-visafo100 .product-left .label-sm { font-size: 8pt; font-weight: 700;}
    .tpl-visafo100 .product-left .product-name { font-size: 12pt; font-weight: 700; letter-spacing: -.7px; text-transform: uppercase; line-height: 1.4;white-space: nowrap; overflow: hidden;   text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

    /* C. Thông tin chi tiết + QR — khối duy nhất co giãn, lấp hết phần còn lại — Trái 73% / Phải 27% */
    .tpl-visafo100 .info-qr-row { display: flex; flex: 1; min-height: 0; overflow: hidden; align-items: flex-start;}
    .tpl-visafo100 .info-col { flex: none; width: 78%; min-width: 0; overflow: hidden; box-sizing: border-box; }
    /* 1. Đổi table-layout sang auto để bảng tự co giãn theo nội dung */
    .tpl-visafo100 .info-table { 
        table-layout: fixed; 
        width: 100%; 
        border-collapse: collapse; 
        font-size: 7.9pt; 
        line-height: 1.4; 
    }

    /* 2. Ép cột Label và Colon thu hẹp hết mức có thể (ôm sát text) */
    .tpl-visafo100 .info-table col.col-label { 
        width: 15.5mm; 
    }
    .tpl-visafo100 .info-table col.col-colon { 
        width: 2.5mm; 
    }

    /* 3. Cột Value sẽ tự động phình to chiếm toàn bộ không gian còn lại */
    .tpl-visafo100 .info-table col.col-value { 
        width: auto; 
    }

    /* 4. Đảm bảo chữ ở cột Label không bao giờ bị rớt dòng để width 1% hoạt động đúng */
    .tpl-visafo100 .info-table td { 
        padding: .45mm 0; 
        vertical-align: central; 
    }
    .tpl-visafo100 .info-table td.lbl { 
        font-weight: 500; 
        white-space: nowrap; /* Bắt buộc để cột ôm sát đoạn text dài nhất */
        padding-right: 0.5mm; /* Tạo khoảng cách nhỏ giữa chữ và dấu hai chấm cho thoáng */
        font-size: 7.5pt;
    }
    .tpl-visafo100 .info-table td.colon { 
        text-align: center; 
        padding-right: 1.5mm; /* Khoảng cách từ dấu hai chấm đến giá trị */
    }
    .tpl-visafo100 .info-table td.val { 
        word-wrap: break-word; 
        overflow-wrap: break-word; 
    }

    /* Các thuộc tính đường kẻ và in đậm giữ nguyên */
    .tpl-visafo100 .info-table tr.weight td { font-weight: 500;}
    .tpl-visafo100 .info-table tr.weight td.val {font-weight: 600; font-size: 8.5pt;}
    .tpl-visafo100 .info-table tr.batch td { font-weight: 500; }
    .tpl-visafo100 .info-table tr.batch td.val {font-weight: 600; font-size: 7.5pt;}
    .tpl-visafo100 .truncate-1-line { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase; }
    .tpl-visafo100 .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.2; text-transform: uppercase;}

    .tpl-visafo100 .qr-col { flex: none; width: 22%; min-width: 0; overflow: hidden; display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: .8mm; text-align: center; box-sizing: border-box; }
    .tpl-visafo100 .qr-box { flex: none; border: 1px solid #000; padding: 0.6mm; box-sizing: border-box; display: flex; align-items: center; justify-content: center; width: 90%; max-width: 100%; max-height: 100%; aspect-ratio: 1 / 1; border-radius: 3px; flex-direction: column;}
    .tpl-visafo100 .qr-box svg { width: 100%; height: 100%; display: block; }
    .tpl-visafo100 .qr-box .qr-placeholder { font-size: 6.5pt; }
    .tpl-visafo100 .qr-box .qr-caption { flex: none; font-size: 6pt; line-height: 1.1; padding-top: 0.6mm;}

    /* D. Footer 1 — bảo quản & sức khỏe — cao tự nhiên theo nội dung */
    .tpl-visafo100 .footer1 { flex: none; overflow: hidden; box-sizing: border-box; display: flex; align-items: center; gap: 1mm; border: 1px solid #000; border-radius: 3px; padding: 0.6mm 1mm 0.6mm 2mm; }
    .tpl-visafo100 .f1-left { flex: 1.4; min-width: 0; overflow: hidden; display: flex; align-items: center; gap: 1.2mm; font-size: 7pt; line-height: 1.2; }
    .tpl-visafo100 .f1-left svg { width: 4mm; height: 4mm; flex: none; }

    /* E. Footer 2 — liên hệ — cao tự nhiên (1 dòng), luôn sát mép dưới cùng của tem */
    .tpl-visafo100 .footer2 { flex: none; overflow: hidden; display: flex; justify-content: space-between; align-items: center; gap: 1.5mm; font-size: 6.8pt; width: 100%; }
    .tpl-visafo100 .footer2 > div { display: flex; align-items: center; gap: .8mm; min-width: 0; overflow: hidden; }
    .tpl-visafo100 .footer2 svg { width: 4mm; height: 4mm; flex: none; }
    .tpl-visafo100 .footer2 span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tpl-visafo100 .footer2 > div:not(:first-child) { flex: none; }

    @media screen { .tpl-visafo100 .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
<div class="label-wrapper tpl-visafo100">
    <div class="label">
        {{-- A. Header --}}
        <div class="header">
            <div class="h-left">
                <div class="co-big">CÔNG TY CỔ PHẦN THỰC PHẨM VISAFO</div>
                <div class="co-slogan">Mang an toàn đến từng bữa ăn</div>
            </div>
            <div class="h-right">
                <img class="logo" src="{{ asset('images/visafo-dark.png') }}" alt="VISAFO">
            </div>
        </div>

        {{-- B. Khối sản phẩm --}}
        <div class="product-block">
            <div class="product-left">
                <div class="label-sm">SẢN PHẨM:</div>
                <div class="product-name">{{ $item->product->name }}</div>
            </div>
        </div>
        {{-- C. Thông tin chi tiết & QR --}}
        <div class="info-qr-row">
            <div class="info-col">
                <table class="info-table">
                    <colgroup>
                        <col class="col-label"><col class="col-colon"><col class="col-value">
                    </colgroup>
                    <tr class="weight">
                        <td class="lbl">Khối lượng</td><td class="colon">:</td>
                        <td class="val">{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) }} kg</td>
                    </tr>
                    <tr class="dashed-sep">
                        <td class="lbl" style="font-weight: 600;">NSX</td><td class="colon">:</td>
                        <td class="val">
                            <div style="display: flex; gap: 3mm; align-items: center;">
                                <span>{{ $log->mfg_date?->format('d/m/Y') ?? '—' }}</span>
                                <span><span style="font-weight: 600; font-size: 7pt;">HSD:</span> {{ $log->exp_date?->format('d/m/Y') ?? '—' }}</span>
                            </div>
                        </td>
                    </tr>
                    @if(!empty($log->supplier_name))
                    <tr>
                        <td class="lbl">Nguồn cung</td><td class="colon">:</td>
                        <td class="val"><div class="truncate-1-line">{{ $log->supplier_name }}</div></td>
                    </tr>
                    @endif
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

        {{-- D. Footer 1: Bảo quản & Sức khỏe --}}
        <div class="footer1">
            <div class="f1-left">
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 14.5V4a2 2 0 0 0-4 0v10.5a4 4 0 1 0 4 0z"/>
                    <line x1="12" y1="8" x2="12" y2="14"/>
                </svg>
                <div>HD bảo quản: Giữ ở nhiệt độ 4 - 10°C. Sử dụng khi còn tươi, rửa sạch trước khi chế biến.</div>
            </div>
        </div>

        {{-- E. Footer 2: Liên hệ --}}
        <div class="footer2">
            <div>
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s7-7.58 7-13a7 7 0 1 0-14 0c0 5.42 7 13 7 13z"/>
                    <circle cx="12" cy="9" r="2.3"/>
                </svg>
                <span>{{ $companyAddress }}</span>
            </div>
            <div>
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 4h4l2 5-2.5 2A12 12 0 0 0 13.5 16.5L15.5 14l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 2 6a2 2 0 0 1 2-2z"/>
                </svg>
                <span>{{ $companyPhone }}</span>
            </div>
            <div>
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <ellipse cx="12" cy="12" rx="4" ry="9"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                </svg>
                <span>{{ $companyWebsite }}</span>
            </div>
        </div>
    </div>
</div>
