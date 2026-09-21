<?php
namespace App\Enums;

/**
 * Permission thực tế của hệ thống Visafo F&B Traceability — khớp 1:1 với các
 * module đang triển khai (Modules/Vendor, Customer, Contract, Product,
 * Compliance, Employee, User, ActivityLog + báo cáo Traceability).
 *
 * Không còn permission "rác" kế thừa từ template SaaS/Agency cũ (CRM Leads,
 * Sales AI, Prompt Mgmt, AI Logs, AI Copilot, CEO Dashboard config, Workflow,
 * SOP, Warehouse, Recall, Subscription, Export Request...) — các module đó
 * không tồn tại trong codebase này.
 */
enum PermissionEnum: string
{
    // ══ PRODUCT (Sản phẩm & Danh mục — bao gồm Hàng hóa NCC khai báo) ══
    case PRODUCT_VIEW   = 'product.view';
    case PRODUCT_MANAGE = 'product.manage';

    // ══ VENDOR (Nhà cung cấp) ══
    case VENDOR_VIEW   = 'vendor.view';
    case VENDOR_MANAGE = 'vendor.manage';

    // ══ GOODS RECEIPT (Nhập hàng & Quản lý Lô) ══
    case GOODS_RECEIPT_VIEW   = 'goods_receipt.view';
    case GOODS_RECEIPT_MANAGE = 'goods_receipt.manage';

    // ══ FOOD INSPECTION (Sổ kiểm thực Bước 1 — QĐ 1246/QĐ-BYT) ══
    case FOOD_INSPECTION_VIEW   = 'food_inspection.view';
    case FOOD_INSPECTION_MANAGE = 'food_inspection.manage';

    // ══ MENU (Thực đơn theo ngày / bữa ăn của cơ sở) ══
    case MENU_VIEW   = 'menu.view';
    case MENU_MANAGE = 'menu.manage';

    // ══ SALES ORDER (Đơn xuất hàng — import phiếu xuất kho MISA) ══
    case SALES_ORDER_VIEW   = 'sales_order.view';
    case SALES_ORDER_MANAGE = 'sales_order.manage';

    // ══ LABEL TEMPLATE (Mẫu tem in) ══
    case LABEL_TEMPLATE_VIEW   = 'label_template.view';
    case LABEL_TEMPLATE_MANAGE = 'label_template.manage';

    // ══ TRACE LOG (Nhật ký TXNG — tra cứu ngược & thu hồi tem) ══
    case TRACE_LOG_VIEW   = 'trace_log.view';
    case TRACE_LOG_MANAGE = 'trace_log.manage';

    // ══ CUSTOMER (Khách hàng B2B F&B) ══
    case CUSTOMER_VIEW   = 'customer.view';
    case CUSTOMER_MANAGE = 'customer.manage';

    // ══ CONTRACT (Hợp đồng nhà cung cấp) ══
    case CONTRACT_VIEW   = 'contract.view';
    case CONTRACT_MANAGE = 'contract.manage';

    // ══ COMPLIANCE (Kho tài liệu & Kiểm tra Readiness) ══
    case COMPLIANCE_VIEW   = 'compliance.view';
    case COMPLIANCE_MANAGE = 'compliance.manage';

    // ══ TRACEABILITY (Báo cáo Truy xuất nguồn gốc — chỉ xem) ══
    case TRACEABILITY_VIEW = 'traceability.view';

    // ══ EMPLOYEE (Nhân sự — Phòng ban/Nhân viên/Hồ sơ y tế & ATTP) ══
    case EMPLOYEE_VIEW   = 'employee.view';
    case EMPLOYEE_MANAGE = 'employee.manage';

    // ══ SYSTEM (Tài khoản người dùng) ══
    case USERS_VIEW   = 'users.view';
    case USERS_MANAGE = 'users.manage';
}
