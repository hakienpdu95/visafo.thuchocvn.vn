<?php

/*
| Định danh pháp lý & địa chỉ trụ sở CỐ ĐỊNH của pháp nhân chủ hệ thống (hệ thống thiết kế riêng cho VISAFO).
| Form "Cập nhật thông tin doanh nghiệp" chỉ hiển thị (readonly) các giá trị này; khi lưu, server luôn ghi đè
| bằng giá trị ở đây — không nhận dữ liệu từ form cho các trường này.
| Địa giới 2 cấp (từ 01/07/2025): Tỉnh/TP → Phường/Xã, không có quận/huyện.
*/
return [
    'locked' => [
        'company_name'  => env('COMPANY_LEGAL_NAME', 'CÔNG TY CỔ PHẦN THỰC PHẨM VISAFO'),
        'tax_code'      => env('COMPANY_TAX_CODE', '0108150635'),
        'province_code' => env('COMPANY_PROVINCE_CODE', '01'),    // Thành phố Hà Nội
        'ward_code'     => env('COMPANY_WARD_CODE', '00466'),     // Xã Phúc Thịnh
        'address'       => env('COMPANY_ADDRESS', 'Số nhà 120 xóm Tây, Xã Phúc Thịnh, Thành phố Hà Nội, Việt Nam'),
    ],
];
