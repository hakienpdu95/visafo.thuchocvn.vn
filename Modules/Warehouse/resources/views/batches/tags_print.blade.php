<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Tem truy vết {{ $batch->internal_batch_code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 0; padding: 10mm; }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4mm;
        }
        .tag {
            border: 1px solid #ccc;
            border-radius: 2mm;
            padding: 3mm;
            text-align: center;
            page-break-inside: avoid;
        }
        .tag svg { width: 32mm; height: 32mm; }
        .tag .sku { font-size: 9pt; font-weight: bold; margin-top: 1mm; }
        .tag .serial { font-size: 10pt; font-weight: bold; letter-spacing: 0.5px; margin-top: 0.5mm; }
        .tag .code { font-size: 6pt; word-break: break-all; color: #999; }
    </style>
</head>
<body>
    <div class="grid">
        @foreach($tags as $tag)
        <div class="tag">
            {!! $tag['svg'] !!}
            <div class="serial">{{ $tag['gs1_serial'] }}</div>
            <div class="sku">{{ $batch->product->sku }}</div>
            <div class="code">{{ $tag['qr_code'] }}</div>
        </div>
        @endforeach
    </div>
</body>
</html>
