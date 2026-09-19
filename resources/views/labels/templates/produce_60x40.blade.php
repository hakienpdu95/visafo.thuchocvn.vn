{{--
    Partial tem nhiệt Nông sản / Rau củ quả — 60x40 mm. Được nhúng bởi labels.master_print (không có <html>/<body>).
    Biến: $item (->product->name, ->salesOrder->delivery_address), $log (->weight_per_label, ->mfg_date, ->exp_date),
          $attributes (thông tin bổ sung EAV), $qrSvg (tuỳ chọn).
    Đã bỏ dòng "Nguồn cung" và "Mã lô". Mọi màu (kể cả nền xanh "Quét QR") sẽ được máy in nhiệt chuyển sang đen đặc.
--}}
@once
<style>
    .tpl-produce .label { width: 60mm; height: 40mm; padding: 2mm 2.5mm; display: flex; gap: 1.5mm; overflow: hidden; background: #fff; }
    .tpl-produce .info { flex: 1; min-width: 0; height: 100%; overflow: hidden; }
    .tpl-produce .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6.5pt; line-height: 1.2; }
    .tpl-produce .info-table th, .tpl-produce .info-table td { padding: .35mm 0; vertical-align: top; border-bottom: 1px dotted #000; }
    /* Cột nhãn cố định; dấu ":" đặt sát mép phải cột nên luôn thẳng hàng dọc */
    .tpl-produce .info-table th { position: relative; width: 13.5mm; padding-right: 1.6mm; text-align: left; font-weight: 700; white-space: nowrap; overflow: hidden; }
    .tpl-produce .info-table th::after { content: ':'; position: absolute; right: .3mm; top: .35mm; }
    .tpl-produce .info-table td { padding-left: 1mm; word-break: break-word; }
    .tpl-produce .info-table .product td { font-size: 7.5pt; font-weight: 700; }
    .tpl-produce .info-table .weight td { font-size: 9pt; font-weight: 700; }

    /* Tối đa 2 dòng, dài hơn thì hiện "..." — không đẩy layout / QR */
    .tpl-produce .truncate-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .tpl-produce .side { width: 16mm; flex: none; display: flex; flex-direction: column; align-items: center; height: 100%; }
    .tpl-produce .logo { width: 16mm; object-fit: contain; display: block; }
    .tpl-produce .qr-box { width: 14mm; height: 14mm; display: flex; align-items: center; justify-content: center; }
    .tpl-produce .qr-box svg { width: 14mm; height: 14mm; display: block; }
    .tpl-produce .qr-box .qr-placeholder { width: 14mm; height: 14mm; border: .3mm dashed #000; font-size: 6pt; display: flex; align-items: center; justify-content: center; }

    @media screen { .tpl-produce .label { box-shadow: 0 1px 4px rgba(0,0,0,.25); } }
</style>
@endonce
<div class="label-wrapper tpl-produce">
    <div class="label">
        <div class="info">
            <table class="info-table">
                <tr class="product">
                    <th>Tên hàng</th>
                    <td><div class="truncate-2-lines">{{ $item->product->name }}</div></td>
                </tr>
                <tr class="weight">
                    <th>Khối lượng</th>
                    <td>{{ str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) }} kg</td>
                </tr>
                <tr>
                    <th>Điểm giao</th>
                    <td><div class="truncate-2-lines">{{ $item->salesOrder->delivery_address ?? '—' }}</div></td>
                </tr>
                <tr>
                    <th>NSX</th>
                    <td>{{ $log->mfg_date?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <th>HSD</th>
                    <td>{{ $log->exp_date?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                {{-- Thông tin bổ sung động (EAV) — lấp đầy khoảng trống dưới HSD --}}
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
            </div>
        </div>
    </div>
</div>
