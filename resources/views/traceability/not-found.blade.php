<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Không tìm thấy mã truy xuất</title>
    @vite(['resources/css/app.css'], 'build/backend')
</head>
<body class="bg-gray-100">
<div class="max-w-md mx-auto bg-gray-50 min-h-screen flex flex-col items-center justify-center px-6 text-center">
    <span class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 shadow-sm ring-1 ring-black/5">
        <img src="{{ asset('images/visafo-logo.svg') }}" alt="VISAFO" class="h-6 w-auto">
    </span>
    <div class="mt-8 flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-red-500">
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    </div>
    <h1 class="mt-4 text-lg font-semibold text-gray-900">Không tìm thấy mã truy xuất</h1>
    <p class="mt-2 text-sm text-gray-500">Mã <span class="font-mono">{{ strtoupper($traceCode) }}</span> không tồn tại hoặc đã bị xóa. Vui lòng quét lại mã QR trên tem hoặc liên hệ đơn vị bán hàng.</p>
</div>
</body>
</html>
