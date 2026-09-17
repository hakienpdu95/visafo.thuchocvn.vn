<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Root segments to strip before parsing
    |--------------------------------------------------------------------------
    | Segments at the start of a route name that represent "admin area"
    | and should not appear as breadcrumb items.
    */
    'skip_root_segments' => ['backend'],

    /*
    |--------------------------------------------------------------------------
    | Model display attributes
    |--------------------------------------------------------------------------
    | When the current page shows a specific model (show/edit), these
    | attributes are tried in order to get the display name.
    */
    'model_name_attributes' => [
        'full_name', 'name', 'title', 'subject', 'label',
        'contract_number', 'batch_code', 'source_code', 'vendor_code', 'customer_code', 'document_number',
        'code',
    ],

    /*
    |--------------------------------------------------------------------------
    | Segment → Vietnamese label map
    |--------------------------------------------------------------------------
    | Maps each route name segment to its display label.
    | null  = skip (segment is hidden from breadcrumbs, e.g. 'index')
    */
    'segments' => [

        // ── CRUD actions ──────────────────────────────────────────────────
        'index'   => null,       // hidden: the resource itself is the crumb
        'show'    => null,       // hidden: model name is used instead
        'create'  => 'Tạo mới',
        'edit'    => 'Chỉnh sửa',
        'store'   => null,
        'update'  => null,
        'destroy' => null,

        // ── Hồ sơ doanh nghiệp & Tuân thủ ───────────────────────────────────
        'internal-compliance'   => 'Hồ sơ doanh nghiệp',
        'internal-facilities'   => 'Cơ sở nội bộ',
        'document-repository'   => 'Kho tài liệu & Minh chứng',
        'document-master-types' => 'Từ điển giấy tờ pháp lý',
        'readiness-check'       => 'Điểm sẵn sàng (Readiness)',
        'traceability'          => 'Báo cáo Truy vết liên thông',
        'compliance-warnings'   => 'Cảnh báo pháp lý & hạn dùng',

        // ── Nhà cung cấp & Sản phẩm ──────────────────────────────────────────
        'vendors'               => 'Quản lý Nhà cung cấp',
        'partner-products'      => 'Hàng hóa Nhà cung cấp',
        'products'              => 'Danh mục Sản phẩm',
        'categories'            => 'Danh mục Nhóm hàng',
        'documents'             => 'Hồ sơ đính kèm',
        'farming-steps'         => 'Công đoạn canh tác',

        // ── Nông hộ / Sản xuất ───────────────────────────────────────────────
        'farming-sources'       => 'Vùng trồng',
        'farming-batches'       => 'Vụ / Lô sản xuất',
        'master-data'           => 'Dữ liệu chuẩn nông nghiệp',
        'pesticides'            => 'Thuốc BVTV',
        'fertilizers'           => 'Phân bón',
        'seeds'                 => 'Giống cây trồng',

        // ── Khách hàng & Hợp đồng & Gói chào hàng ───────────────────────────
        'customers'             => 'Quản lý Khách hàng',
        'contacts'              => 'Liên hệ',
        'delivery-points'       => 'Điểm giao hàng',
        'contracts'             => 'Quản lý Hợp đồng',
        'sales-packages'        => 'Gói chào hàng',
        'items'                 => 'Tài liệu trong gói',
        'status'                => 'Trạng thái',
        'export'                => 'Xuất dữ liệu',

        // ── Nhân sự ──────────────────────────────────────────────────────────
        'employees'             => 'Danh sách nhân viên',
        'departments'           => 'Phòng ban / Bộ phận',
        'health-records'        => 'Hồ sơ sức khỏe',

        // ── Tài khoản & Phân quyền ───────────────────────────────────────────
        'users'                 => 'Danh sách tài khoản',
        'roles'                 => 'Vai trò',
        'permissions'           => 'Quyền hạn',

        // ── Nhật ký hoạt động ────────────────────────────────────────────────
        'activitylog'           => 'Nhật ký hoạt động',

        // ── Khác ─────────────────────────────────────────────────────────────
        'notifications'         => 'Thông báo',
        'preferences'           => 'Tùy chọn',
        'orders'                => 'Đơn hàng',
        'reports'               => 'Báo cáo',
        'settings'              => 'Cài đặt',

        // ── Auth / Profile ───────────────────────────────────────────────────
        'profile'               => 'Hồ sơ cá nhân',
        'me'                    => 'Thông tin cá nhân',

    ],

];
