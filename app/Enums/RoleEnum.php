<?php
namespace App\Enums;

enum RoleEnum: string
{
    case CEO       = 'ceo';
    case SALES     = 'sales';
    case OPS       = 'ops';
    case MARKETING = 'marketing';
    case HR        = 'hr';
    case AI_OP     = 'ai_operator';
    case ADMIN     = 'system_admin';
    case VIEWER    = 'viewer';

    // ── Business Consulting OS (BCOS) — vai trò trong Business Project ──
    // Global role (đủ để Ringlesoft laravel-process-approval route theo Spatie Role);
    // giới hạn "chỉ project được phân công" nằm ở Policy + business_project_members,
    // mirror đúng cách role SALES hiện có bị LeadPolicy narrow theo "lead của mình".
    case LEAD_CONSULTANT   = 'lead_consultant';
    case CONSULTANT        = 'consultant';
    case BUSINESS_ANALYST  = 'ba';
    case PROJECT_MANAGER   = 'pm';
    case CUSTOMER_SUCCESS  = 'customer_success';

    public function label(): string
    {
        return match($this) {
            self::CEO       => 'CEO / Founder',
            self::SALES     => 'Sales Team',
            self::OPS       => 'Operations',
            self::MARKETING => 'Marketing',
            self::HR        => 'HR / Admin Staff',
            self::AI_OP     => 'AI Operator',
            self::ADMIN     => 'System Admin',
            self::VIEWER    => 'Viewer / Partner',
            self::LEAD_CONSULTANT  => 'Lead Consultant',
            self::CONSULTANT       => 'Consultant',
            self::BUSINESS_ANALYST => 'Business Analyst',
            self::PROJECT_MANAGER  => 'Project Manager',
            self::CUSTOMER_SUCCESS => 'Customer Success',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::CEO       => 'badge-primary',
            self::ADMIN     => 'badge-error',
            self::OPS       => 'badge-warning',
            self::SALES     => 'badge-success',
            self::HR        => 'badge-info',
            self::AI_OP     => 'badge-secondary',
            self::MARKETING => 'badge-accent',
            self::VIEWER    => 'badge-ghost',
            self::LEAD_CONSULTANT  => 'badge-primary',
            self::CONSULTANT       => 'badge-info',
            self::BUSINESS_ANALYST => 'badge-warning',
            self::PROJECT_MANAGER  => 'badge-secondary',
            self::CUSTOMER_SUCCESS => 'badge-accent',
        };
    }

    public function visibleModules(): array
    {
        return match($this) {
            self::CEO    => ['ceo_dashboard','crm','sales_ai','tasks','sop','workflow','prompt','ai_logs','ai_copilot','users','reports','activity_log','business_project'],
            self::SALES  => ['crm','sales_ai','tasks','sop','reports','ai_copilot'],
            self::OPS    => ['ceo_dashboard','crm','tasks','sop','workflow','ai_logs','ai_copilot','reports'],
            self::MARKETING => ['crm','sales_ai','tasks','sop','reports','ai_copilot'],
            self::HR     => ['tasks','sop','users','reports','ai_copilot'],
            self::AI_OP  => ['ceo_dashboard','crm','tasks','sop','workflow','prompt','ai_logs','ai_copilot','reports'],
            self::ADMIN  => ['ceo_dashboard','crm','sales_ai','tasks','sop','workflow','prompt','ai_logs','ai_copilot','users','roles','reports','integrations','activity_log','vertical_templates','business_project'],
            self::VIEWER => ['ceo_dashboard','tasks','sop','reports'],
            self::LEAD_CONSULTANT  => ['business_project','tasks','sop','reports'],
            self::CONSULTANT       => ['business_project','tasks','sop'],
            self::BUSINESS_ANALYST => ['business_project','tasks','sop'],
            self::PROJECT_MANAGER  => ['business_project','tasks','reports'],
            self::CUSTOMER_SUCCESS => ['business_project'],
        };
    }
}