{{--
    Partial tem nhiệt Nông Sản Khô / Ngũ Cốc — 60x40 mm. Nhúng bởi labels.master_print (không có <html>/<body>).
    Biến: $item (->product->name, ->salesOrder->customer_name/delivery_address), $log (->weight_per_label, ->mfg_date,
          ->exp_date, ->supplier_name, ->batch_code), $attributes (thông tin bổ sung EAV), $qrSvg (tuỳ chọn).
    Khác biệt so với produce_60x40: có 1 dòng cảnh báo độ ẩm (viền chấm gạch, chữ nghiêng) chèn dưới HSD — phù hợp
    hàng lưu kho lâu ngày.
--}}
@once
<style>
    .tpl-dried .label { width: 60mm; height: 40mm; padding: 2mm 2.5mm; display: flex; gap: 1.5mm; overflow: hidden; background: #fff; }
    .tpl-dried .info { flex: 1; min-width: 0; height: 100%; overflow: hidden; }
    .tpl-dried .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6pt; line-height: 1.2; }
    .tpl-dried .info-table th, .tpl-dried .info-table td { padding: .3mm 0; vertical-align: top; border-bottom: .5px dotted #666; }
    .tpl-dried .info-table th { position: relative; width: 36%; padding-right: 1.6mm; text-align: left; font-weight: 700; white-space: nowrap; overflow: hidden; }
    .tpl-dried .info-table th::after { content: ':'; position: absolute; right: .3mm; top: .3mm; }
    .tpl-dried .info-table td { padding-left: 1mm; word-break: break-word; }
    .tpl-dried .info-table .product td { font-size: 7pt; font-weight: 700; text-transform: uppercase; }
    .tpl-dried .info-table .weight td { font-size: 7pt; font-weight: 700; }
    .tpl-dried .info-table .block-divider th,
    .tpl-dried .info-table .block-divider td { border-top: .75pt solid #000; padding-top: .6mm; }
    /* Cảnh báo độ ẩm — 1 ô gộp 2 cột, viền chấm gạch, chữ nghiêng (nhẹ hơn cảnh báo nhiệt độ của tem đông lạnh) */
    .tpl-dried .info-table .humidity-warning td { border: .75pt dashed #000; text-align: center; font-style: italic; font-size: 5.5pt; padding: .6mm 0; }

    .tpl-dried .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .tpl-dried .side { width: 16mm; flex: none; display: flex; flex-direction: column; align-items: center; height: 100%; }
    .tpl-dried .logo { width: 16mm; object-fit: contain; display: block; }
    .tpl-dried .qr-box { width: 14mm; height: 14mm; display: flex; align-items: center; justify-content: center; }
    .tpl-dried .qr-box svg { width: 14mm; height: 14mm; display: block; }
    .tpl-dried .trace-code { text-align: center; font-family: 'Courier New', monospace; font-size: 5pt; font-weight: 700; letter-spacing: .1mm; line-height: 1; margin-top: .5mm; }
    .tpl-dried .qr-box .qr-placeholder { width: 14mm; height: 14mm; border: .3mm dashed #000; font-size: 6pt; display: flex; align-items: center; justify-content: center; }

    @media screen { .tpl-dried .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
<div class="label-wrapper tpl-dried">
    <div class="label">
        <div class="info">
            <table class="info-table">
                <tr class="product">
                    <th>Sản phẩm</th>
                    <td><div class="truncate-2-lines">{{ $item->product->name }}</div></td>
                </tr>
                <tr>
                    <th>Khách hàng</th>
                    <td><div class="truncate-2-lines">{{ $item->salesOrder->customer_name ?? '—' }}</div></td>
                </tr>
                <tr>
                    <th>Điểm giao</th>
                    <td><div class="truncate-2-lines">{{ $item->salesOrder->delivery_address ?? '—' }}</div></td>
                </tr>
                <tr class="weight">
                    <th>Khối lượng</th>
                    <td>{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) }} kg</td>
                </tr>
                <tr class="block-divider">
                    <th>NSX</th>
                    <td>{{ $log->mfg_date?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <th>HSD</th>
                    <td>{{ $log->exp_date?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr class="humidity-warning">
                    <td colspan="2">Bảo quản nơi khô ráo, thoáng mát — tránh ẩm mốc</td>
                </tr>
                <tr>
                    <th>Nguồn cung</th>
                    <td><div class="truncate-2-lines">{{ $log->supplier_name ?? '—' }}</div></td>
                </tr>
                <tr>
                    <th>Mã lô</th>
                    <td>{{ $log->batch_code ?? '—' }}</td>
                </tr>
                {{-- Thông tin bổ sung động (EAV) — lấp đầy khoảng trống còn lại --}}
                @foreach($attributes as $attr)
                <tr>
                    <th>{{ $attr->attribute_key }}</th>
                    <td><div class="truncate-2-lines">{{ $attr->attribute_value }}</div></td>
                </tr>
                @endforeach
            </table>
        </div>

        <div class="side">
            <img class="logo" src="{{ asset('images/logo.png') }}" alt="VISAFO">
            <div>
                <div class="qr-box">
                    @isset($qrSvg){!! $qrSvg !!}@else<div class="qr-placeholder">QR</div>@endisset
                </div>
                @if(!empty($log->trace_code))<div class="trace-code">{{ strtoupper($log->trace_code) }}</div>@endif
            </div>
        </div>
    </div>
</div>
