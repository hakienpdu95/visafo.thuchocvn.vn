{{--
    Partial tem nhiệt VISAFO Rau Củ — khổ lớn 100x75 mm. Nhúng bởi labels.master_print (không có <html>/<body>).
    Biến: $item (->product->name, ->salesOrder->customer_name/delivery_address), $log (->weight_per_label, ->mfg_date,
          ->exp_date, ->supplier_name, ->batch_code), $attributes (thông tin bổ sung EAV), $qrSvg (tuỳ chọn).
    Thông tin công ty (địa chỉ/SĐT/website) là dữ liệu tĩnh của thương hiệu, không đến từ đơn hàng — đặt cố định
    bên dưới (giống cách các mẫu khác cố định logo VISAFO); sửa trực tiếp khi công ty đổi thông tin liên hệ.
    Chỉ dùng đen/trắng (không xám, không đổ bóng) cho phù hợp máy in nhiệt; icon là SVG nhúng thẳng để in được
    khi hệ thống offline.

    KHUÔN CỨNG (rigid structure): mỗi khối trong .label có height/max-height CỐ ĐỊNH + overflow:hidden riêng.
    Bắt buộc làm vậy vì <table> và text dài không tự co theo flex-shrink — nếu chỉ đặt overflow:hidden ở .label
    ngoài cùng, nội dung tràn của MỘT khối vẫn vẽ đè lên khối liền kề (đã tái hiện lỗi này bằng dữ liệu dài trước
    khi sửa). Còn dư chỗ tới đâu, info-qr-row (bảng thông tin + QR) giãn hết tới đó — đây là khối duy nhất được
    phép co giãn (flex:1), mọi khối khác đều cứng.
--}}
@php
    $companyAddress = 'Số 120, Xóm Tây, Xã Phúc Thịnh, Tỉnh Ninh Bình';
    $companyPhone   = '0972 402 619';
    $companyWebsite = 'www.visafo.com.vn';
@endphp
@once
<style>
    .tpl-visafo100 .label {
        width: 100mm; height: 75mm; box-sizing: border-box; overflow: hidden; position: relative;
        background: #fff; color: #000; border: 2px solid #000; border-radius: 8px; padding: 2mm;
        display: flex; flex-direction: column; justify-content: space-between; gap: 1mm;
    }

    /* A. Header: trái (tên công ty), giữa (tagline script), phải (logo) — cứng 9mm */
    .tpl-visafo100 .header { height: 9mm; flex: none; overflow: hidden; display: flex; align-items: center; gap: 2mm; border-bottom: 1px solid #000; padding-bottom: .8mm; }
    .tpl-visafo100 .h-left { flex: 1; min-width: 0; overflow: hidden; }
    .tpl-visafo100 .h-left .co-small { font-size: 6pt; font-weight: 700; letter-spacing: .2mm; }
    .tpl-visafo100 .h-left .co-big { font-size: 10pt; font-weight: 800; letter-spacing: .1mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tpl-visafo100 .h-left .co-slogan { font-size: 5.5pt; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tpl-visafo100 .h-mid { flex: none; display: flex; align-items: center; gap: 1mm; max-width: 26mm; overflow: hidden; }
    .tpl-visafo100 .h-mid svg { flex: none; width: 4mm; height: 4mm; }
    .tpl-visafo100 .h-mid .tagline { font-family: 'Segoe Script', 'Brush Script MT', cursive; font-style: italic; font-size: 7pt; line-height: 1.05; text-align: center; }
    .tpl-visafo100 .h-right { flex: none; display: flex; align-items: center; gap: .8mm; }
    .tpl-visafo100 .h-right svg { width: 5mm; height: 5mm; flex: none; }
    .tpl-visafo100 .h-right span { font-size: 11pt; font-weight: 800; letter-spacing: .3mm; }

    /* B. Khối sản phẩm — nền đen chữ trắng — cứng, giới hạn tối đa 16mm */
    .tpl-visafo100 .product-block { flex: none; max-height: 16mm; overflow: hidden; display: flex; align-items: center; background: #000; color: #fff; border-radius: 5px; padding: 1.4mm 2.2mm; gap: 2mm; box-sizing: border-box; }
    .tpl-visafo100 .product-left { flex: 1; min-width: 0; overflow: hidden; }
    .tpl-visafo100 .product-left .label-sm { font-size: 6pt; font-weight: 700; letter-spacing: .3mm; }
    .tpl-visafo100 .product-left .product-name { font-size: 15px; font-weight: 700; text-transform: uppercase; line-height: 1.15; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .tpl-visafo100 .product-divider { flex: none; align-self: stretch; width: 0; border-left: 1px solid #fff; }
    .tpl-visafo100 .product-right { flex: none; width: 28mm; overflow: hidden; display: flex; align-items: center; gap: 1.2mm; font-size: 6.3pt; font-weight: 700; line-height: 1.2; text-transform: uppercase; }
    .tpl-visafo100 .product-right svg { width: 4.5mm; height: 4.5mm; flex: none; }

    /* C. Thông tin chi tiết + QR — khối duy nhất co giãn, lấp hết phần còn lại */
    .tpl-visafo100 .info-qr-row { flex: 1 1 auto; min-height: 0; overflow: hidden; display: flex; gap: 2mm; }
    .tpl-visafo100 .info-col { flex: 0 1 70%; min-width: 0; overflow: hidden; }
    .tpl-visafo100 .info-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 8px; line-height: 1.2; }
    .tpl-visafo100 .info-table col.col-label { width: 35%; }
    .tpl-visafo100 .info-table col.col-colon { width: 5%; }
    .tpl-visafo100 .info-table col.col-value { width: 60%; }
    .tpl-visafo100 .info-table td { padding: 1px 0; vertical-align: top; }
    .tpl-visafo100 .info-table td.lbl { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tpl-visafo100 .info-table td.colon { text-align: center; }
    .tpl-visafo100 .info-table td.val { word-wrap: break-word; overflow-wrap: break-word; }
    .tpl-visafo100 .info-table tr.weight td { font-size: 9px; font-weight: 800; }
    .tpl-visafo100 .info-table tr.dashed-sep td { border-top: .75pt dashed #000; padding-top: 3px; }
    .tpl-visafo100 .info-table tr.batch td { font-weight: 800; }
    .tpl-visafo100 .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .tpl-visafo100 .qr-col { flex: 0 0 28mm; min-width: 0; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .8mm; text-align: center; }
    .tpl-visafo100 .qr-box { flex: none; border: 1.4px solid #000; padding: 1mm; box-sizing: border-box; display: flex; align-items: center; justify-content: center; width: 20mm; height: 20mm; max-width: 100%; max-height: 100%; }
    .tpl-visafo100 .qr-box svg { width: 100%; height: 100%; display: block; }
    .tpl-visafo100 .qr-box .qr-placeholder { font-size: 6.5pt; }
    .tpl-visafo100 .qr-caption { flex: none; font-size: 5.3pt; line-height: 1.15; }

    /* D. Footer 1 — bảo quản & sức khỏe — cứng, tối đa 10mm */
    .tpl-visafo100 .footer1 { flex: none; max-height: 10mm; overflow: hidden; box-sizing: border-box; display: flex; align-items: center; gap: 2mm; border: 1px solid #000; border-radius: 4px; padding: 1mm 2mm; }
    .tpl-visafo100 .f1-left { flex: 1.4; min-width: 0; overflow: hidden; display: flex; align-items: center; gap: 1.2mm; font-size: 7px; line-height: 1.1; }
    .tpl-visafo100 .f1-left svg { width: 4.5mm; height: 4.5mm; flex: none; }
    .tpl-visafo100 .f1-divider { flex: none; align-self: stretch; width: 0; border-left: 1px solid #000; }
    .tpl-visafo100 .f1-right { flex: 1; min-width: 0; overflow: hidden; display: flex; align-items: center; gap: 1.2mm; font-size: 7px; font-weight: 700; line-height: 1.1; text-align: center; }
    .tpl-visafo100 .f1-right svg { width: 4mm; height: 4mm; flex: none; }

    /* E. Footer 2 — liên hệ — cứng 4.5mm, luôn một dòng */
    .tpl-visafo100 .footer2 { flex: none; height: 4.5mm; overflow: hidden; display: flex; justify-content: space-between; align-items: center; gap: 1.5mm; font-size: 7px; width: 100%; }
    .tpl-visafo100 .footer2 > div { display: flex; align-items: center; gap: .8mm; min-width: 0; overflow: hidden; }
    .tpl-visafo100 .footer2 svg { width: 3.4mm; height: 3.4mm; flex: none; }
    .tpl-visafo100 .footer2 span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    @media screen { .tpl-visafo100 .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
<div class="label-wrapper tpl-visafo100">
    <div class="label">
        {{-- A. Header --}}
        <div class="header">
            <div class="h-left">
                <div class="co-small">CÔNG TY CỔ PHẦN</div>
                <div class="co-big">THỰC PHẨM VISAFO</div>
                <div class="co-slogan">Mang an toàn đến từng bữa ăn</div>
            </div>
            <div class="h-mid">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 21C3 10 11 3 21 3c0 10-8 18-18 18z" fill="#000"/>
                    <path d="M5 19C10 13 14 9 19 5" fill="none" stroke="#fff" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                <div class="tagline">Rau củ an toàn<br>Thuận tự nhiên</div>
            </div>
            <div class="h-right">
                {{-- Logo VISAFO: icon lá cây nền trong suốt (không khung/nền đen) + wordmark --}}
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 21C3 10 11 3 21 3c0 10-8 18-18 18z" fill="#000"/>
                    <path d="M5 19C10 13 14 9 19 5" fill="none" stroke="#fff" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                <span>VISAFO</span>
            </div>
        </div>

        {{-- B. Khối sản phẩm --}}
        <div class="product-block">
            <div class="product-left">
                <div class="label-sm">SẢN PHẨM:</div>
                <div class="product-name">{{ $item->product->name }}</div>
            </div>
            <div class="product-divider"></div>
            <div class="product-right">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 21C3 10 11 3 21 3c0 10-8 18-18 18z" fill="#fff"/>
                    <path d="M5 19C10 13 14 9 19 5" fill="none" stroke="#000" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                <div>RAU CỦ TƯƠI<br>AN TOÀN<br>TRUY XUẤT NGUỒN GỐC</div>
            </div>
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
                        <td class="val"><div class="truncate-2-lines">{{ $item->salesOrder->customer_name ?? '—' }}</div></td>
                    </tr>
                    <tr>
                        <td class="lbl">Điểm giao</td><td class="colon">:</td>
                        <td class="val"><div class="truncate-2-lines">{{ $item->salesOrder->delivery_address ?? '—' }}</div></td>
                    </tr>
                    <tr class="weight">
                        <td class="lbl">Khối lượng</td><td class="colon">:</td>
                        <td class="val">{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) }} kg</td>
                    </tr>
                    <tr class="dashed-sep">
                        <td class="lbl">NSX</td><td class="colon">:</td>
                        <td class="val">{{ $log->mfg_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">HSD</td><td class="colon">:</td>
                        <td class="val">{{ $log->exp_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Nguồn cung</td><td class="colon">:</td>
                        <td class="val"><div class="truncate-2-lines">{{ $log->supplier_name ?? '—' }}</div></td>
                    </tr>
                    <tr class="batch">
                        <td class="lbl">Mã lô</td><td class="colon">:</td>
                        <td class="val">{{ $log->batch_code ?? '—' }}</td>
                    </tr>
                    {{--
                        Không hiện thêm dòng thuộc tính bổ sung (EAV) ở khổ này: 7 dòng cố định trên đã lấp gần
                        hết khung cứng khi dữ liệu dài (tên KH/địa chỉ/nguồn cung dài) — thêm dòng biến động dễ
                        bị overflow:hidden cắt cụt giữa chừng, để lại vệt chữ thừa. Cần hiện thêm thì tăng
                        info-qr-row bằng cách giảm bớt chiều cao header/product-block/footer trước.
                    --}}
                </table>
            </div>
            <div class="qr-col">
                <div class="qr-box">
                    @isset($qrSvg){!! $qrSvg !!}@else<div class="qr-placeholder">QR</div>@endisset
                </div>
                <div class="qr-caption">Quét QR để xem<br>thông tin truy xuất</div>
            </div>
        </div>

        {{-- D. Footer 1: Bảo quản & Sức khỏe --}}
        <div class="footer1">
            <div class="f1-left">
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 14.5V4a2 2 0 0 0-4 0v10.5a4 4 0 1 0 4 0z"/>
                    <line x1="12" y1="8" x2="12" y2="14"/>
                </svg>
                <div>Bảo quản: Giữ ở nhiệt độ 4 - 10°C. Sử dụng khi còn tươi, rửa sạch trước khi chế biến.</div>
            </div>
            <div class="f1-divider"></div>
            <div class="f1-right">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 21C3 10 11 3 21 3c0 10-8 18-18 18z" fill="#000"/>
                    <path d="M5 19C10 13 14 9 19 5" fill="none" stroke="#fff" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                <div>Vì sức khỏe cộng đồng<br>Vì một bữa ăn an toàn</div>
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
