<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Xem trước mẫu tem — lỗi</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; color: #1f2937; }
        .box { max-width: 36rem; margin: 3rem auto; background: #fff; border: 1px solid #fecaca; border-radius: .5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { font-size: 1.1rem; color: #b91c1c; margin: 0 0 .75rem; }
        p { margin: .4rem 0; font-size: .9rem; line-height: 1.5; }
        code { background: #f3f4f6; padding: .1rem .35rem; border-radius: .25rem; font-size: .85rem; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{{ $title }}</h1>
        <p>Mẫu tem: <strong>{{ $template->name }}</strong></p>
        <p>View Path: <code>{{ $template->view_path }}</code></p>
        <p>{{ $message }}</p>
        <p><a href="{{ route('backend.label-templates.edit', $template) }}">Sửa mẫu tem</a></p>
    </div>
</body>
</html>
