{{--
    Partial tem nhiệt Thực Phẩm Sơ Chế / Chế Biến Sẵn — 60x40 mm. Nhúng bởi labels.master_print (không có <html>/<body>).
    Biến: $item (->product->name, ->salesOrder->customer_name/delivery_address), $log (->weight_per_label, ->mfg_date,
          ->exp_date, ->supplier_name, ->batch_code), $attributes (thông tin bổ sung EAV — VD: Thành phần, HDSD), $qrSvg.
    Khác biệt so với produce_60x40: attribute nào có tên chứa "thành phần"/"hdsd"/"hướng dẫn" được cho phép hiển thị
    tối đa 3 dòng (truncate-3-lines) thay vì 2, vì đây là nội dung trọng tâm của mẫu tem này.
--}}
@once
<style>
    .tpl-precooked .label { width: 60mm; height: 40mm; padding: 2mm 2.5mm; display: flex; gap: 1.5mm; overflow: hidden; background: #fff; }
    .tpl-precooked .info { flex: 1; min-width: 0; height: 100%; overflow: hidden; }
    .tpl-precooked .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6pt; line-height: 1.2; }
    .tpl-precooked .info-table th, .tpl-precooked .info-table td { padding: .3mm 0; vertical-align: top; border-bottom: .5px dotted #666; }
    .tpl-precooked .info-table th { position: relative; width: 36%; padding-right: 1.6mm; text-align: left; font-weight: 700; white-space: nowrap; overflow: hidden; }
    .tpl-precooked .info-table th::after { content: ':'; position: absolute; right: .3mm; top: .3mm; }
    .tpl-precooked .info-table td { padding-left: 1mm; word-break: break-word; }
    .tpl-precooked .info-table .product td { font-size: 7pt; font-weight: 700; text-transform: uppercase; }
    .tpl-precooked .info-table .weight td { font-size: 7pt; font-weight: 700; }
    .tpl-precooked .info-table .block-divider th,
    .tpl-precooked .info-table .block-divider td { border-top: .75pt solid #000; padding-top: .6mm; }

    .tpl-precooked .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    /* Thành phần / HDSD — nội dung trọng tâm, cho phép tối đa 3 dòng thay vì 2 */
    .tpl-precooked .truncate-3-lines { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    .tpl-precooked .side { width: 16mm; flex: none; display: flex; flex-direction: column; align-items: center; height: 100%; }
    .tpl-precooked .logo { width: 16mm; object-fit: contain; display: block; }
    .tpl-precooked .qr-box { width: 14mm; height: 14mm; display: flex; align-items: center; justify-content: center; }
    .tpl-precooked .qr-box svg { width: 14mm; height: 14mm; display: block; }
    .tpl-precooked .trace-code { text-align: center; font-family: 'Courier New', monospace; font-size: 5pt; font-weight: 700; letter-spacing: .1mm; line-height: 1; margin-top: .5mm; }
    .tpl-precooked .qr-box .qr-placeholder { width: 14mm; height: 14mm; border: .3mm dashed #000; font-size: 6pt; display: flex; align-items: center; justify-content: center; }

    @media screen { .tpl-precooked .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
@php
    // Thành phần / HDSD là nội dung trọng tâm của mẫu tem này — cho hiển thị dài hơn (3 dòng) các attribute khác.
    $isFeaturedAttr = fn ($a) => str_contains(mb_strtolower($a->attribute_key), 'thành phần')
        || str_contains(mb_strtolower($a->attribute_key), 'hdsd')
        || str_contains(mb_strtolower($a->attribute_key), 'hướng dẫn');
@endphp
<div class="label-wrapper tpl-precooked">
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
                <tr>
                    <th>Nguồn cung</th>
                    <td><div class="truncate-2-lines">{{ $log->supplier_name ?? '—' }}</div></td>
                </tr>
                <tr>
                    <th>Mã lô</th>
                    <td>{{ $log->batch_code ?? '—' }}</td>
                </tr>
                {{-- Thành phần / HDSD (3 dòng) + các attribute khác (2 dòng) --}}
                @foreach($attributes as $attr)
                <tr>
                    <th>{{ $attr->attribute_key }}</th>
                    <td><div class="{{ $isFeaturedAttr($attr) ? 'truncate-3-lines' : 'truncate-2-lines' }}">{{ $attr->attribute_value }}</div></td>
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
