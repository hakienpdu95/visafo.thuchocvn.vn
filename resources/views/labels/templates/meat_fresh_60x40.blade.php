{{--
    Partial tem nhiệt Thịt Tươi Sống (Heo/Bò/Gà) — 60x40 mm. Nhúng bởi labels.master_print (không có <html>/<body>).
    Biến: $item (->product->name, ->salesOrder->customer_name/delivery_address), $log (->weight_per_label, ->mfg_date,
          ->exp_date, ->supplier_name, ->batch_code), $attributes (thông tin bổ sung EAV), $qrSvg (tuỳ chọn).
    Khác biệt so với produce_60x40: ưu tiên tách riêng 1 dòng "Ngày giết mổ" lên đầu khối EAV (khớp attribute_key
    chứa "giết mổ" do nhân viên nhập ở "Thông tin in bổ sung"); các attribute còn lại (bảo quản mát, mã kiểm dịch...)
    vẫn in bình thường qua cơ chế EAV chung, không cần hardcode riêng từng loại.
--}}
@once
<style>
    .tpl-meat .label { width: 60mm; height: 40mm; padding: 2mm 2.5mm; display: flex; gap: 1.5mm; overflow: hidden; background: #fff; }
    .tpl-meat .info { flex: 1; min-width: 0; height: 100%; overflow: hidden; }
    .tpl-meat .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6pt; line-height: 1.2; }
    .tpl-meat .info-table th, .tpl-meat .info-table td { padding: .3mm 0; vertical-align: top; border-bottom: .5px dotted #666; }
    .tpl-meat .info-table th { position: relative; width: 36%; padding-right: 1.6mm; text-align: left; font-weight: 700; white-space: nowrap; overflow: hidden; }
    .tpl-meat .info-table th::after { content: ':'; position: absolute; right: .3mm; top: .3mm; }
    .tpl-meat .info-table td { padding-left: 1mm; word-break: break-word; }
    .tpl-meat .info-table .product td { font-size: 7pt; font-weight: 700; text-transform: uppercase; }
    .tpl-meat .info-table .weight td { font-size: 7pt; font-weight: 700; }
    .tpl-meat .info-table .block-divider th,
    .tpl-meat .info-table .block-divider td { border-top: .75pt solid #000; padding-top: .6mm; }
    /* Dòng "Ngày giết mổ" nhấn mạnh nhẹ để phân biệt với các attribute tự nhập khác */
    .tpl-meat .info-table .slaughter-date th,
    .tpl-meat .info-table .slaughter-date td { font-weight: 700; }

    .tpl-meat .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .tpl-meat .side { width: 16mm; flex: none; display: flex; flex-direction: column; align-items: center; height: 100%; }
    .tpl-meat .logo { width: 16mm; object-fit: contain; display: block; }
    .tpl-meat .qr-box { width: 14mm; height: 14mm; display: flex; align-items: center; justify-content: center; }
    .tpl-meat .qr-box svg { width: 14mm; height: 14mm; display: block; }
    .tpl-meat .trace-code { text-align: center; font-family: 'Courier New', monospace; font-size: 5pt; font-weight: 700; letter-spacing: .1mm; line-height: 1; margin-top: .5mm; }
    .tpl-meat .qr-box .qr-placeholder { width: 14mm; height: 14mm; border: .3mm dashed #000; font-size: 6pt; display: flex; align-items: center; justify-content: center; }

    @media screen { .tpl-meat .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
@php
    // Tách riêng attribute "Ngày giết mổ" (nếu nhân viên đã nhập ở modal Cấu hình In Tem) để đưa lên đầu khối EAV.
    $slaughterAttr = $attributes->first(fn ($a) => str_contains(mb_strtolower($a->attribute_key), 'giết mổ'));
    $restAttributes = $slaughterAttr ? $attributes->reject(fn ($a) => $a->is($slaughterAttr)) : $attributes;
@endphp
<div class="label-wrapper tpl-meat">
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
                <tr class="slaughter-date">
                    <th>Ngày giết mổ</th>
                    <td>{{ $slaughterAttr->attribute_value ?? '—' }}</td>
                </tr>
                {{-- Các attribute bổ sung còn lại (bảo quản mát, mã kiểm dịch thú y...) qua cơ chế EAV chung --}}
                @foreach($restAttributes as $attr)
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
