{{--
    Master layout in tem — khung HTML dùng chung cho in đơn lẻ, in lại, in hàng loạt và Xem trước mẫu tem.

    Biến nhận vào:
      $items     — danh sách "mục in" (object), mỗi mục gồm:
                     ->viewPath   Blade view (partial) của mẫu tem, VD: labels.templates.produce_60x40
                     ->item       dòng đơn xuất (->product->name, ->salesOrder->delivery_address...)
                     ->log        bản ghi in (->weight_per_label, ->mfg_date, ->exp_date...)
                     ->order      đơn xuất hàng (tuỳ chọn)
                     ->attributes collection thông tin bổ sung (->attribute_key, ->attribute_value)
                     ->copies     số tem cần in cho mục này (mặc định 1)
                     ->qrSvg      markup <svg> QR (tuỳ chọn)
      $autoPrint — true → tự mở hộp thoại in rồi đóng tab

    Mỗi template tem là một PARTIAL: chỉ chứa một khối <div class="label-wrapper"> (+ CSS tự đóng gói bằng @once,
    có tiền tố riêng để nhiều loại tem cùng nằm trong một lần in mà không đè CSS nhau).
    Master lặp theo số tem và ngắt trang sau mỗi tem, trừ tem cuối cùng (tránh nhả ra 1 tem trắng).
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>In tem</title>
    <style>
        @page { size: 60mm 40mm; margin: 0; }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { background: #fff; color: #000; font-family: Arial, Helvetica, sans-serif; }

        /* Ép máy in ngắt trang sau mỗi tem */
        .page-break { page-break-after: always; break-after: page; }

        @media screen {
            body { background: #e5e7eb; padding: 8mm; }
            .label-wrapper { margin: 0 auto 6mm; }
        }
    </style>
</head>
<body>
@php
    $totalLabels = collect($items)->sum(fn ($entry) => max(1, (int) ($entry->copies ?? 1)));
    $labelNo = 0;
@endphp
@foreach ($items as $entry)
    @for ($i = 0; $i < max(1, (int) ($entry->copies ?? 1)); $i++)
        @php($labelNo++)
        <div class="{{ $labelNo < $totalLabels ? 'page-break' : '' }}">
            @include($entry->viewPath, [
                'item'       => $entry->item,
                'log'        => $entry->log,
                'printLog'   => $entry->log,
                'order'      => $entry->order ?? null,
                'attributes' => $entry->attributes ?? collect(),
                'qrSvg'      => $entry->qrSvg ?? null,
            ])
        </div>
    @endfor
@endforeach

@if (!empty($autoPrint))
<script>window.onload = function() { window.print(); window.close(); }</script>
@endif
</body>
</html>
