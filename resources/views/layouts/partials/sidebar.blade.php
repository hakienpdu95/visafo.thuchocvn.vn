<aside class="sidebar" id="sidebar">

    <div class="brand">
        <div class="brand-logo">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="brand-logo-img">
        </div>
        {{-- brand-name ẩn: tên đã có trong logo image --}}
    </div>

    <nav class="nav-wrap">
        <p class="section-title">Tổng quan</p>
        <div class="nav-group">
            <a href="{{ route('backend.dashboard') }}"
               class="nav-link {{ request()->routeIs('backend.dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="nav-label">Dashboard</span>
            </a>

            <a href="{{ route('backend.notifications.index') }}"
               class="nav-link {{ request()->routeIs('backend.notifications.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="nav-label">Thông báo</span>
            </a>

            @can('compliance.view')
            <a href="{{ route('backend.compliance-warnings.index') }}"
               class="nav-link {{ request()->routeIs('backend.compliance-warnings.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <span class="nav-label">Cảnh báo pháp lý & hạn dùng</span>
            </a>
            @endcan
        </div>

        <p class="section-title" style="margin-top:16px;">Đối tác & Chuỗi cung ứng</p>
        <div class="nav-group">
            @canany(['vendor.view', 'contract.view', 'product.view'])
            <details {{ request()->routeIs('backend.vendors.*', 'backend.contracts.*', 'backend.partner-products.*', 'backend.customers.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.vendors.*', 'backend.contracts.*', 'backend.partner-products.*', 'backend.customers.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h18v4H3V3zm2 4h14v14H5V7zm3 4h8m-8 4h5"/></svg>
                    <span class="nav-label">Đối tác & Chuỗi cung ứng</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    @can('vendor.view')
                    <a href="{{ route('backend.vendors.index') }}" class="sub-link {{ request()->routeIs('backend.vendors.*') ? 'active' : '' }}">Quản lý Nhà cung cấp</a>
                    @endcan
                    @can('product.view')
                    <a href="{{ route('backend.partner-products.index') }}" class="sub-link {{ request()->routeIs('backend.partner-products.*') ? 'active' : '' }}">Hàng hóa Nhà cung cấp</a>
                    @endcan
                    @can('contract.view')
                    <a href="{{ route('backend.contracts.index') }}" class="sub-link {{ request()->routeIs('backend.contracts.*') ? 'active' : '' }}">Quản lý Hợp đồng</a>
                    @endcan
                    <a href="{{ route('backend.customers.index') }}" class="sub-link {{ request()->routeIs('backend.customers.*') ? 'active' : '' }}">Quản lý Khách hàng</a>
                </div>
            </details>
            @endcanany
        </div>

        <p class="section-title" style="margin-top:16px;">Sản phẩm & Truy xuất</p>
        <div class="nav-group">
            @can('product.view')
            <details {{ request()->routeIs('backend.products.*', 'backend.categories.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.products.*', 'backend.categories.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                    <span class="nav-label">Sản phẩm & Truy xuất</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.products.index') }}" class="sub-link {{ request()->routeIs('backend.products.*') ? 'active' : '' }}">Danh mục Sản phẩm chuẩn</a>
                    <a href="{{ route('backend.categories.index') }}" class="sub-link {{ request()->routeIs('backend.categories.*') ? 'active' : '' }}">Danh mục Nhóm hàng</a>
                </div>
            </details>
            @endcan
        </div>

        <p class="section-title" style="margin-top:16px;">Quản trị Tuân thủ</p>
        <div class="nav-group">
            @canany(['product.manage', 'compliance.view'])
            <details {{ request()->routeIs('backend.document-repository.*', 'backend.document-master-types.*', 'backend.readiness-check.*', 'backend.traceability.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.document-repository.*', 'backend.document-master-types.*', 'backend.readiness-check.*', 'backend.traceability.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                    <span class="nav-label">Quản trị Tuân thủ</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    @can('product.manage')
                    <a href="{{ route('backend.document-repository.index') }}" class="sub-link {{ request()->routeIs('backend.document-repository.*') ? 'active' : '' }}">Kho tài liệu & Minh chứng</a>
                    <a href="{{ route('backend.document-master-types.index') }}" class="sub-link {{ request()->routeIs('backend.document-master-types.*') ? 'active' : '' }}">Từ điển giấy tờ pháp lý</a>
                    @endcan
                    @can('compliance.view')
                    <a href="{{ route('backend.traceability.index') }}" class="sub-link {{ request()->routeIs('backend.traceability.*') ? 'active' : '' }}">Báo cáo Truy vết liên thông</a>
                    <a href="{{ route('backend.readiness-check.index') }}" class="sub-link {{ request()->routeIs('backend.readiness-check.*') ? 'active' : '' }}">Kiểm tra Readiness</a>
                    @endcan
                </div>
            </details>
            @endcanany
        </div>

        <p class="section-title" style="margin-top:16px;">Quản trị Hệ thống & Nội bộ</p>
        <div class="nav-group">

            @can('employee.view')
            <details {{ request()->routeIs('backend.departments.*', 'backend.employees.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.departments.*', 'backend.employees.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="nav-label">Nhân sự & Y tế</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.employees.index') }}" class="sub-link {{ request()->routeIs('backend.employees.*') ? 'active' : '' }}">Danh sách nhân viên</a>
                    <a href="{{ route('backend.departments.index') }}" class="sub-link {{ request()->routeIs('backend.departments.*') ? 'active' : '' }}">Phòng ban / Bộ phận</a>
                </div>
            </details>
            @endcan

            <details {{ request()->routeIs('backend.users.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.users.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="nav-label">Tài khoản & Phân quyền</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.users.index') }}" class="sub-link {{ request()->routeIs('backend.users.index') ? 'active' : '' }}">Danh sách tài khoản</a>
                    <a href="{{ route('backend.users.create') }}" class="sub-link {{ request()->routeIs('backend.users.create') ? 'active' : '' }}">Thêm tài khoản</a>
                </div>
            </details>

            @can('activitylog.view')
            <details {{ request()->routeIs('activitylog.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('activitylog.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="nav-label">Nhật ký hoạt động</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('activitylog.index') }}"
                       class="sub-link {{ request()->routeIs('activitylog.index') ? 'active' : '' }}">
                       Danh sách log
                    </a>
                </div>
            </details>
            @endcan

        </div>

    </nav>
</aside>
