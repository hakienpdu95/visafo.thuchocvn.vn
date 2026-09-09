<?php
// Thêm permission mới: chỉ sửa file này + chạy php artisan permissions:sync
use App\Enums\RoleEnum as R;
use App\Enums\PermissionEnum as P;

return [

    R::CEO->value => [
        // CEO Dashboard: Full
        P::CEO_DASH_FULL->value,
        // Subscription: View
        P::SUBSCRIPTION_VIEW->value,
        // Sales AI: Full (view + use)
        P::SALES_AI_VIEW->value,
        P::SALES_AI_USE->value,
        // Prompt: View only
        P::PROMPT_VIEW->value,
        // AI Logs: View summary
        P::AI_LOGS_VIEW->value,
        // AI Copilot: Use + View usage
        P::AI_COPILOT_USE->value,
        P::AI_COPILOT_VIEW_USAGE->value,
        // Users: View
        P::USERS_VIEW->value,
        P::VENDOR_VIEW->value,
        P::CONTRACT_VIEW->value,
        P::PRODUCT_VIEW->value,
        P::WAREHOUSE_VIEW->value,
        P::RECALL_VIEW->value,
        P::COMPLIANCE_VIEW->value,
        P::EMPLOYEE_VIEW->value,
        // Export dữ liệu nhạy cảm: Full — CEO phê duyệt export (SoD)
        P::EXPORT_REQUEST_VIEW->value,
        P::EXPORT_REQUEST_APPROVE->value,
    ],

    R::SALES->value => [
        // Sales AI: Use (dùng output, không config)
        P::SALES_AI_USE->value,
        // AI Copilot: Use
        P::AI_COPILOT_USE->value,
    ],

    R::OPS->value => [
        // Subscription: View
        P::SUBSCRIPTION_VIEW->value,
        // CEO Dashboard: Limited (view, không có AI brief, không có approve)
        P::CEO_DASH_VIEW->value,
        // AI Logs: Limited (view, không full)
        P::AI_LOGS_VIEW->value,
        // AI Copilot: Use + View usage
        P::AI_COPILOT_USE->value,
        P::AI_COPILOT_VIEW_USAGE->value,
        P::VENDOR_VIEW->value,
        P::VENDOR_MANAGE->value,
        P::CONTRACT_VIEW->value,
        P::CONTRACT_MANAGE->value,
        P::PRODUCT_VIEW->value,
        P::PRODUCT_MANAGE->value,
        P::WAREHOUSE_VIEW->value,
        P::WAREHOUSE_MANAGE->value,
        P::RECALL_VIEW->value,
        P::RECALL_MANAGE->value,
        P::COMPLIANCE_VIEW->value,
        P::COMPLIANCE_MANAGE->value,
        P::EMPLOYEE_VIEW->value,
        P::EMPLOYEE_MANAGE->value,
        // Export dữ liệu nhạy cảm: View (minh bạch nội bộ)
        P::EXPORT_REQUEST_VIEW->value,
    ],

    R::MARKETING->value => [
        // Sales AI: Limited (view, không use/config)
        P::SALES_AI_VIEW->value,
        // AI Copilot: Use
        P::AI_COPILOT_USE->value,
        // Export dữ liệu nhạy cảm: View (minh bạch nội bộ)
        P::EXPORT_REQUEST_VIEW->value,
    ],

    R::HR->value => [
        // AI Copilot: Use
        P::AI_COPILOT_USE->value,
        // Users: Limited (tạo user nội bộ, onboarding)
        P::USERS_HR->value,
        // Nhân sự: Full — HR quản lý phòng ban/nhân viên/hồ sơ y tế & ATTP
        P::EMPLOYEE_VIEW->value,
        P::EMPLOYEE_MANAGE->value,
        // Export dữ liệu nhạy cảm: View (minh bạch nội bộ)
        P::EXPORT_REQUEST_VIEW->value,
    ],

    R::AI_OP->value => [
        // CEO Dashboard: Limited
        P::CEO_DASH_VIEW->value,
        // Sales AI: Config prompt
        P::SALES_AI_CONFIG_PROMPT->value,
        // Prompt Management: Full
        P::PROMPT_FULL->value,
        // AI Logs: Full
        P::AI_LOGS_FULL->value,
        // AI Copilot: Use + Config + View usage
        P::AI_COPILOT_USE->value,
        P::AI_COPILOT_CONFIG->value,
        P::AI_COPILOT_VIEW_USAGE->value,
        // Export dữ liệu nhạy cảm: View (minh bạch nội bộ)
        P::EXPORT_REQUEST_VIEW->value,
    ],

    R::ADMIN->value => [
        // Subscription: View
        P::SUBSCRIPTION_VIEW->value,
        // Config trên tất cả module (độc lập với data)
        P::CEO_DASH_CONFIG->value,
        P::SALES_AI_CONFIG->value,
        P::PROMPT_ADMIN_CONFIG->value,
        P::AI_LOGS_FULL->value,
        // AI Copilot: Use + Config + View usage
        P::AI_COPILOT_USE->value,
        P::AI_COPILOT_CONFIG->value,
        P::AI_COPILOT_VIEW_USAGE->value,
        // User & roles management
        P::USERS_MANAGE->value,
        P::ROLES_MANAGE->value,
        // System
        P::INTEGRATION_MANAGE->value,
        P::AUDIT_VIEW->value,
        P::SYSTEM_CONFIG->value,
        P::VENDOR_VIEW->value,
        P::VENDOR_MANAGE->value,
        P::CONTRACT_VIEW->value,
        P::CONTRACT_MANAGE->value,
        P::PRODUCT_VIEW->value,
        P::PRODUCT_MANAGE->value,
        P::WAREHOUSE_VIEW->value,
        P::WAREHOUSE_MANAGE->value,
        P::RECALL_VIEW->value,
        P::RECALL_MANAGE->value,
        P::COMPLIANCE_VIEW->value,
        P::COMPLIANCE_MANAGE->value,
        P::EMPLOYEE_VIEW->value,
        P::EMPLOYEE_MANAGE->value,
        // Export dữ liệu nhạy cảm: Full — System Admin phê duyệt export (SoD)
        P::EXPORT_REQUEST_VIEW->value,
        P::EXPORT_REQUEST_APPROVE->value,
    ],

    R::VIEWER->value => [
        // CEO Dashboard: View limited
        P::CEO_DASH_VIEW->value,
        // Export dữ liệu nhạy cảm: View (minh bạch nội bộ)
        P::EXPORT_REQUEST_VIEW->value,
    ],

    // ─────────────────────────────────────────────────────────────────
    // Business Consulting OS (BCOS) role — permission gốc (business_project.*,
    // business_context.*, ...) đã bị xóa cùng Modules/BusinessProject
    // (cleanup/remove-non-competency-modules). Giữ RoleEnum case (không thuộc
    // phạm vi sửa của đợt này) nhưng không còn permission nào để gán — để trống
    // thay vì tham chiếu case enum đã xóa (sẽ lỗi PHP fatal).
    // ─────────────────────────────────────────────────────────────────
    R::LEAD_CONSULTANT->value  => [],
    R::CONSULTANT->value       => [],
    R::BUSINESS_ANALYST->value => [],
    R::PROJECT_MANAGER->value  => [],
    R::CUSTOMER_SUCCESS->value => [],
];
