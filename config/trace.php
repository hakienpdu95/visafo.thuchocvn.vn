<?php

/*
| Thông tin đơn vị đóng gói / phân phối hiển thị trên trang truy xuất công khai
| (Thông tư 02/2024/TT-BKHCN). Ưu tiên lấy từ "Trụ sở chính" ở Hồ sơ doanh nghiệp;
| các giá trị dưới đây chỉ là dự phòng khi chưa khai báo.
*/
return [
    'company_name'    => env('TRACE_COMPANY_NAME', 'VISAFO'),
    'company_address' => env('TRACE_COMPANY_ADDRESS', ''),
    'company_hotline' => env('TRACE_COMPANY_HOTLINE', ''),
];
