{{--
    Partial tem nhiệt Hải Sản Đông Lạnh — 60x40 mm. Nhúng bởi labels.master_print (không có <html>/<body>).
    Biến: $item (->product->name, ->salesOrder->customer_name/delivery_address), $log (->weight_per_label, ->mfg_date,
          ->exp_date, ->supplier_name, ->batch_code), $attributes (thông tin bổ sung EAV, VD: hướng dẫn rã đông), $qrSvg.
    Khác biệt so với produce_60x40: có 1 dòng cảnh báo in đậm, viền đen "BẢO QUẢN Ở NHIỆT ĐỘ -18°C" chèn ngay dưới HSD.
--}}
@once
<style>
    .tpl-seafood .label { width: 60mm; height: 40mm; padding: 2mm 2.5mm; display: flex; gap: 1.5mm; overflow: hidden; background: #fff; }
    .tpl-seafood .info { flex: 1; min-width: 0; height: 100%; overflow: hidden; }
    .tpl-seafood .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6pt; line-height: 1.2; }
    .tpl-seafood .info-table th, .tpl-seafood .info-table td { padding: .3mm 0; vertical-align: top; border-bottom: .5px dotted #666; }
    .tpl-seafood .info-table th { position: relative; width: 36%; padding-right: 1.6mm; text-align: left; font-weight: 700; white-space: nowrap; overflow: hidden; }
    .tpl-seafood .info-table th::after { content: ':'; position: absolute; right: .3mm; top: .3mm; }
    .tpl-seafood .info-table td { padding-left: 1mm; word-break: break-word; }
    .tpl-seafood .info-table .product td { font-size: 7pt; font-weight: 700; text-transform: uppercase; }
    .tpl-seafood .info-table .weight td { font-size: 7pt; font-weight: 700; }
    .tpl-seafood .info-table .block-divider th,
    .tpl-seafood .info-table .block-divider td { border-top: .75pt solid #000; padding-top: .6mm; }
    /* Cảnh báo nhiệt độ bảo quản — 1 ô gộp 2 cột, viền đen, chữ đậm, căn giữa */
    .tpl-seafood .info-table .cold-warning td { border: .75pt solid #000; border-bottom: .75pt solid #000; text-align: center; font-weight: 700; font-size: 6.5pt; padding: .7mm 0; letter-spacing: .1mm; }

    .tpl-seafood .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .tpl-seafood .side { width: 16mm; flex: none; display: flex; flex-direction: column; align-items: center; height: 100%; }
    .tpl-seafood .logo { width: 16mm; object-fit: contain; display: block; }
    .tpl-seafood .qr-box { width: 14mm; height: 14mm; display: flex; align-items: center; justify-content: center; }
    .tpl-seafood .qr-box svg { width: 14mm; height: 14mm; display: block; }
    .tpl-seafood .trace-code { text-align: center; font-family: 'Courier New', monospace; font-size: 5pt; font-weight: 700; letter-spacing: .1mm; line-height: 1; margin-top: .5mm; }
    .tpl-seafood .qr-box .qr-placeholder { width: 14mm; height: 14mm; border: .3mm dashed #000; font-size: 6pt; display: flex; align-items: center; justify-content: center; }

    @media screen { .tpl-seafood .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
<div class="label-wrapper tpl-seafood">
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
                <tr class="cold-warning">
                    <td colspan="2">BẢO QUẢN Ở NHIỆT ĐỘ -18°C</td>
                </tr>
                <tr>
                    <th>Nguồn cung</th>
                    <td><div class="truncate-2-lines">{{ $log->supplier_name ?? '—' }}</div></td>
                </tr>
                <tr>
                    <th>Mã lô</th>
                    <td>{{ $log->batch_code ?? '—' }}</td>
                </tr>
                {{-- Thông tin bổ sung động (EAV) — VD: hướng dẫn rã đông --}}
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
