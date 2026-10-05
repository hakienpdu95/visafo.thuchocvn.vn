<?php

/*
| Thông tin đơn vị đóng gói / phân phối hiển thị trên trang truy xuất công khai
| (Thông tư 02/2024/TT-BKHCN). Ưu tiên lấy từ "Trụ sở chính" ở Hồ sơ doanh nghiệp;
| các giá trị dưới đây chỉ là dự phòng khi chưa khai báo.
*/
return [
    // Domain công khai mã hóa vào QR trên tem (/trace/{code}). Tem đã in không đổi được → đặt domain thật ở production.
    'public_url'      => env('TRACE_PUBLIC_URL') ?: env('APP_URL'),

    'company_name'    => env('TRACE_COMPANY_NAME', 'VISAFO'),
    // Tên pháp nhân: "Đã xác thực bởi…", đơn vị đóng gói/phân phối, header (in hoa bằng CSS).
    // Không lấy tên cơ sở "Trụ sở chính" vì đó là nhãn nội bộ của địa điểm, không phải tên công ty.
    // company_name ở trên là thương hiệu ngắn (VISAFO). Slogan dùng làm tiêu đề phụ khi sản phẩm chưa có nhóm hàng.
    'company_legal_name' => env('TRACE_COMPANY_LEGAL_NAME', 'Công ty cổ phần thực phẩm Visafo'),
    'company_slogan'  => env('TRACE_COMPANY_SLOGAN', 'Mang an toàn đến từng bữa ăn'),
    'company_address' => env('TRACE_COMPANY_ADDRESS', ''),
    'company_hotline' => env('TRACE_COMPANY_HOTLINE', ''),

    // Vendor đại diện "VISAFO - Vùng trồng tự quản" (ID, cách nhau dấu phẩy). Vùng trồng/lô canh tác gắn vendor này
    // hiển thị là "Vùng trồng của VISAFO". Không khai báo thì nhận diện theo MST trùng MST trụ sở chính.
    'own_vendor_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRACE_OWN_VENDOR_IDS', ''))))),

    // Khối "Câu chuyện thương hiệu" ở tab Thương hiệu (tạm cấu hình tại đây; xuống dòng = tách đoạn).
    'brand_story' => env('TRACE_BRAND_STORY', "VISAFO ra đời với sứ mệnh mang thực phẩm an toàn, minh bạch nguồn gốc đến từng bữa ăn của người Việt.\nChúng tôi hợp tác trực tiếp với các nông trại, nhà cung cấp đạt chuẩn; mọi lô hàng đều được kiểm soát chất lượng, sơ chế, đóng gói và gắn mã truy xuất riêng.\nTầm nhìn của VISAFO là trở thành đơn vị cung ứng thực phẩm sạch đáng tin cậy hàng đầu cho bếp ăn tập thể, trường học và gia đình."),
];
