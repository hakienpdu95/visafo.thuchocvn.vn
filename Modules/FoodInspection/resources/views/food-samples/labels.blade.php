{{--
    Trang in Tem Lưu Mẫu (Mẫu số 4) — mỗi mẫu một tem 50x30 mm, ngắt trang sau mỗi tem, tự mở hộp thoại in.
    Không tự đóng tab để nhân viên đối chiếu lại tem sau khi in.
    Biến: $log, $samples (FoodSampleDetail[]), $minHours.
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>In tem lưu mẫu — {{ $log->customer_name }}</title>
    <style>
        @page { size: 50mm 30mm; margin: 0; }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { background: #fff; color: #000; font-family: Arial, Helvetica, sans-serif; }

        .page-break { page-break-after: always; break-after: page; }

        @media screen {
            body { background: #e5e7eb; padding: 8mm; }
            .label-wrapper { margin: 0 auto 6mm; width: 50mm; }
        }
    </style>
</head>
<body>
@foreach($samples as $index => $sample)
    <div class="{{ $index < count($samples) - 1 ? 'page-break' : '' }}">
        @include('labels.templates.sample_label_50x30', ['sample' => $sample, 'log' => $log])
    </div>
@endforeach

<script>
    window.onload = function () { window.print(); };
</script>
</body>
</html>
