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
