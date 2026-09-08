<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Cuộn tem tiền định danh</title>
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
        .tag .serial { font-size: 8pt; font-weight: bold; margin-top: 1mm; word-break: break-all; }
        .tag .seq { font-size: 7pt; color: #555; }
    </style>
</head>
<body>
    <div class="grid">
        @foreach($tags as $tag)
        <div class="tag">
            {!! $tag['svg'] !!}
            <div class="serial">{{ $tag['gs1_serial'] }}</div>
            <div class="seq">SEQ: {{ str_pad((string) $tag['visual_sequence'], 6, '0', STR_PAD_LEFT) }}</div>
        </div>
        @endforeach
    </div>
</body>
</html>
