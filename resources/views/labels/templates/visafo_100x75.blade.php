{{--
    Partial tem nhiệt VISAFO Rau Củ — khổ lớn 100x75 mm. Nhúng bởi labels.master_print (không có <html>/<body>).
    Biến: $item (->product->name, ->salesOrder->customer_name/delivery_address), $log (->weight_per_label, ->mfg_date,
          ->exp_date, ->supplier_name, ->batch_code), $attributes (thông tin bổ sung EAV), $qrSvg (tuỳ chọn).
    Thông tin công ty (địa chỉ/SĐT/website) là dữ liệu tĩnh của thương hiệu, không đến từ đơn hàng — đặt cố định
    bên dưới (giống cách các mẫu khác cố định logo VISAFO); sửa trực tiếp khi công ty đổi thông tin liên hệ.
    Chỉ dùng đen/trắng (không xám, không đổ bóng) cho phù hợp máy in nhiệt; icon là SVG nhúng thẳng để in được
    khi hệ thống offline.

    Các khối (header/product-block/footer1/footer2) cao TỰ NHIÊN theo nội dung — không ép height/max-height cố
    định bằng mm, để tránh vừa cắt cụt nội dung vừa chiếm oan diện tích khi không cần. Mỗi khối chỉ giữ
    overflow:hidden + line-clamp trên phần text để chặn tràn cục bộ khi dữ liệu quá dài. info-qr-row (bảng
    thông tin + QR) là khối DUY NHẤT co giãn (flex:1), lấy hết phần chiều cao còn lại sau khi các khối kia đã
    chiếm chỗ theo nội dung thực tế của chúng.
--}}
@php
    $companyAddress = 'Số 120, Xóm Tây, Xã Phúc Thịnh, TP Hà Nội';
    $companyPhone   = '0972 402 619';
    $companyWebsite = 'www.visafo.com.vn';
@endphp
@once
<style>
    .tpl-visafo100 .label {
        width: 100mm; height: 75mm; box-sizing: border-box; overflow: hidden; position: relative;
        background: #fff; color: #000; border: 2px solid #000; border-radius: 5px; padding: 1.1mm 1.5mm;
        display: flex; flex-direction: column; justify-content: space-between; gap: 1.4mm;
    }

    /* A. Header: trái (tên công ty), giữa (tagline script), phải (logo) — cao tự nhiên theo nội dung, không ép cứng số mm */
    .tpl-visafo100 .header { flex: none; overflow: hidden; display: flex; align-items: flex-end; gap: 1.2mm; }
    .tpl-visafo100 .h-left { flex: 1; min-width: 0; overflow: hidden; border-bottom: 1px solid #000; padding-bottom: .8mm; }
    .tpl-visafo100 .h-left .co-small { font-size: 7.5pt; font-weight: 700; letter-spacing: .2mm; }
    .tpl-visafo100 .h-left .co-big { font-size: 12.5pt; font-weight: bold; letter-spacing: 0; font-stretch: condensed; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.1; }
    .tpl-visafo100 .h-left .co-slogan { font-size: 6pt; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1; margin-top: 0; }
    .tpl-visafo100 .h-mid { flex: none; display: flex; align-items: center; gap: .8mm; max-width: 30mm; overflow: hidden; margin-right: 6mm;}
    .tpl-visafo100 .h-mid .tagline { font-family: 'Segoe Script', 'Brush Script MT', cursive; font-style: italic; font-size: 7.5pt; line-height: 1.2; margin: 0; text-align: center; font-weight: 400;}
    .tpl-visafo100 .h-right { flex: none; display: flex; align-items: flex-end; gap: .8mm; }
    .tpl-visafo100 .h-right img { width: 12mm; object-fit: contain; flex: none; }

    /* B. Khối sản phẩm — nền đen chữ trắng — cao tự nhiên theo nội dung; font vừa phải + line-clamp để không phình to */
    .tpl-visafo100 .product-block { flex: none; overflow: hidden; display: flex; align-items: center; background: #000; color: #fff; border-radius: 5px; padding: 1.4mm 2.2mm; gap: 2mm; box-sizing: border-box; }
    .tpl-visafo100 .product-left { flex: 1; min-width: 0; overflow: hidden; }
    .tpl-visafo100 .product-left .label-sm { font-size: 8pt; font-weight: 700;}
    .tpl-visafo100 .product-left .product-name { font-size: 14.5pt; font-weight: 900; letter-spacing: -.3px; text-transform: uppercase; line-height: 1.2;white-space: nowrap; overflow: hidden;   text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .tpl-visafo100 .product-divider { flex: none; align-self: stretch; width: 0; border-left: 1px solid #fff; }
    .tpl-visafo100 .product-right { flex: none; width: 28mm; overflow: hidden; display: flex; align-items: center; gap: 1.2mm; font-size: 6pt; font-weight: 700; line-height: 1.2; text-transform: uppercase; }

    /* C. Thông tin chi tiết + QR — khối duy nhất co giãn, lấp hết phần còn lại — Trái 73% / Phải 27% */
    .tpl-visafo100 .info-qr-row { display: flex; flex: 1; min-height: 0; overflow: hidden; align-items: flex-start;}
    .tpl-visafo100 .info-col { flex: none; width: 78%; min-width: 0; overflow: hidden; box-sizing: border-box; }
    .tpl-visafo100 .info-table { table-layout: fixed; width: 100%; border-collapse: collapse; font-size: 7pt; line-height: 1.1; margin-top: 1.2mm;}
    .tpl-visafo100 .info-table col.col-label { width: 32%; }
    .tpl-visafo100 .info-table col.col-colon { width: 5%; }
    .tpl-visafo100 .info-table col.col-value { width: 63%; }
    .tpl-visafo100 .info-table td { padding: .3mm 0; vertical-align: middle; }
    .tpl-visafo100 .info-table td.lbl { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tpl-visafo100 .info-table td.colon { text-align: center; }
    .tpl-visafo100 .info-table td.val { word-wrap: break-word; overflow-wrap: break-word; }
    .tpl-visafo100 .info-table tr.weight td { font-weight: 500; padding-bottom: 1mm;}
    .tpl-visafo100 .info-table tr.weight td.val {font-weight: 600; font-size: 7.5pt;}
    .tpl-visafo100 .info-table tr.dashed-sep td { border-top: 1px solid #000; padding-top: 1mm; }
    .tpl-visafo100 .info-table tr.batch td { font-weight: 500; }
    .tpl-visafo100 .info-table tr.batch td.val {font-weight: 600; font-size: 7.5pt;}
    .tpl-visafo100 .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .tpl-visafo100 .qr-col { flex: none; width: 22%; min-width: 0; overflow: hidden; display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: .8mm; text-align: center; box-sizing: border-box; }
    .tpl-visafo100 .qr-box { flex: none; border: 1px solid #000; padding: 1mm; box-sizing: border-box; display: flex; align-items: center; justify-content: center; width: 90%; max-width: 100%; max-height: 100%; aspect-ratio: 1 / 1; border-radius: 3px; flex-direction: column;}
    .tpl-visafo100 .qr-box svg { width: 100%; height: 100%; display: block; }
    .tpl-visafo100 .qr-box .qr-placeholder { font-size: 6.5pt; }
    .tpl-visafo100 .qr-box .qr-caption { flex: none; font-size: 5pt; line-height: 1.1; padding-top: 0.6mm;}

    /* D. Footer 1 — bảo quản & sức khỏe — cao tự nhiên theo nội dung */
    .tpl-visafo100 .footer1 { flex: none; overflow: hidden; box-sizing: border-box; display: flex; align-items: center; gap: 1mm; border: 1px solid #000; border-radius: 3px; padding: 1mm; }
    .tpl-visafo100 .f1-left { flex: 1.4; min-width: 0; overflow: hidden; display: flex; align-items: center; gap: 1.2mm; font-size: 6.4pt; line-height: 1.4; }
    .tpl-visafo100 .f1-left svg { width: 5mm; height: 5mm; flex: none; }
    .tpl-visafo100 .f1-divider { flex: none; align-self: stretch; width: 0; border-left: 1px solid #000; }
    .tpl-visafo100 .f1-right { flex: 1; min-width: 0; overflow: hidden; display: flex; align-items: center; gap: 1.2mm; font-size: 6.4pt; line-height: 1.4; text-align: center; }
    .tpl-visafo100 .f1-right svg { width: 5mm; height: 5mm; flex: none; }

    /* E. Footer 2 — liên hệ — cao tự nhiên (1 dòng), luôn sát mép dưới cùng của tem */
    .tpl-visafo100 .footer2 { flex: none; overflow: hidden; display: flex; justify-content: space-between; align-items: center; gap: 1.5mm; font-size: 6.2pt; width: 100%; margin-top: 1mm; }
    .tpl-visafo100 .footer2 > div { display: flex; align-items: center; gap: .8mm; min-width: 0; overflow: hidden; }
    .tpl-visafo100 .footer2 svg { width: 4mm; height: 4mm; flex: none; }
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
                <div class="tagline">Rau củ an toàn<br>Thuận tự nhiên</div>
            </div>
            <div class="h-right">
                {{-- Logo VISAFO: icon lá cây nền trong suốt (không khung/nền đen) + wordmark --}}
                <img class="logo" src="{{ asset('images/visafo-dark.png') }}" alt="VISAFO">
            </div>
        </div>

        {{-- C. Thông tin chi tiết & QR --}}
        <div class="info-qr-row">
            <div class="info-col">
                {{-- B. Khối sản phẩm --}}
                <div class="product-block">
                    <div class="product-left">
                        <div class="label-sm">SẢN PHẨM:</div>
                        <div class="product-name">{{ $item->product->name }}</div>
                    </div>
                    <div class="product-divider"></div>
                    <div class="product-right">
                        <div>RAU CỦ TƯƠI<br>AN TOÀN<br>TRUY XUẤT NGUỒN GỐC</div>
                    </div>
                </div>
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
                        <td class="lbl">Ngày sản xuất (NSX)</td><td class="colon">:</td>
                        <td class="val">{{ $log->mfg_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Hạn sử dụng (HSD)</td><td class="colon">:</td>
                        <td class="val">{{ $log->exp_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    @if(!empty($log->supplier_name))
                    <tr>
                        <td class="lbl">Nguồn cung</td><td class="colon">:</td>
                        <td class="val"><div class="truncate-2-lines">{{ $log->supplier_name }}</div></td>
                    </tr>
                    @endif
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
