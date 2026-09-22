{{--
    Master layout in tem — khung HTML dùng chung cho in đơn lẻ, in lại, in hàng loạt và Xem trước mẫu tem.

    Biến nhận vào:
      $items     — danh sách "mục in" (object); MỖI MỤC LÀ MỘT TEM (một PrintLog có trace_code riêng), gồm:
                     ->viewPath   Blade view (partial) của mẫu tem, VD: labels.templates.produce_60x40
                     ->item       dòng đơn xuất (->product->name, ->salesOrder->delivery_address...)
                     ->log        bản ghi in (->weight_per_label, ->mfg_date, ->exp_date...)
                     ->order      đơn xuất hàng (tuỳ chọn)
                     ->attributes collection thông tin bổ sung (->attribute_key, ->attribute_value)
                     ->qrSvg      markup <svg> QR (tuỳ chọn)
      $autoPrint — true → tự mở hộp thoại in rồi đóng tab

    Mỗi template tem là một PARTIAL: chỉ chứa một khối <div class="label-wrapper"> (+ CSS tự đóng gói bằng @once,
    có tiền tố riêng để nhiều loại tem cùng nằm trong một lần in mà không đè CSS nhau).
    Master lặp qua từng tem và ngắt trang sau mỗi tem, trừ tem cuối cùng (tránh nhả ra 1 tem trắng).

    Khổ giấy in (@page) được suy ra từ ->size (VD "100x75") của TEM ĐẦU TIÊN trong $items — không đọc theo từng
    tem, vì @page là quy tắc toàn trang in. Không sao vì một lần in luôn dùng chung một mẫu tem (một
    print_session_id ứng với một label_template_id duy nhất — xem PrintSalesOrderItemLabelAction). Thiếu/không
    hợp lệ thì rơi về mặc định 60x40mm (khổ tem phổ biến nhất hệ thống, giữ đúng hành vi trước khi có ->size).
--}}
@php
    $pageSize = '60mm 40mm';
    $rawSize = $items[0]->size ?? null;
    if ($rawSize && preg_match('/^(\d+(?:\.\d+)?)\s*[xX]\s*(\d+(?:\.\d+)?)$/', trim($rawSize), $m)) {
        $pageSize = $m[1] . 'mm ' . $m[2] . 'mm';
    }
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>In tem</title>
    <style>
        @page { size: {{ $pageSize }}; margin: 0; }

        * {
            box-sizing: border-box; margin: 0; padding: 0;
            /* Không có dòng này, Chrome/Edge mặc định KHÔNG in nền màu (nền đen khối sản phẩm, viền...)
               trừ khi người dùng tự tick "Background graphics" trong hộp thoại in. */
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
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
@foreach ($items as $index => $entry)
    {{-- Mỗi mục = MỘT tem (một PrintLog, một trace_code/QR riêng). Ngắt trang sau mỗi tem, trừ tem cuối. --}}
    <div class="{{ $index < count($items) - 1 ? 'page-break' : '' }}">
        @include($entry->viewPath, [
            'item'       => $entry->item,
            'log'        => $entry->log,
            'printLog'   => $entry->log,
            'order'      => $entry->order ?? null,
            'attributes' => $entry->attributes ?? collect(),
            'qrSvg'      => $entry->qrSvg ?? null,
        ])
    </div>
@endforeach

@if (!empty($autoPrint))
<script>
    // Chỉ mở hộp thoại in tự động.
    // Tuyệt đối không dùng window.close() ở đây để nhân viên xem lại tem trên tab sau khi in/hủy.
    window.onload = function() {
        window.print();
    };
</script>
@endif
</body>
</html>
