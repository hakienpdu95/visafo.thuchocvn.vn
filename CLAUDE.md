# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.4), SQLite (dev) / configurable for production
- **Frontend**: Vite 8, Tailwind CSS 4, DaisyUI 5, Alpine.js 3, jQuery
- **Auth**: Laravel Fortify + Sanctum
- **RBAC**: Spatie Laravel Permissions (roles + permissions)
- **Modules**: NWIDART Laravel Modules

## Common Commands

```bash
# Development
npm run dev                  # Vite dev server (port 5173)
php artisan serve            # Laravel dev server
php artisan queue:listen     # Queue worker

# Build
npm run build                # Frontend assets
npx vite build --config vite.config.backend.js  # Backend module bundles

# Database
php artisan migrate
php artisan db:seed
php artisan db:seed --class="Modules\Auth\Database\Seeders\AuthDatabaseSeeder"
php artisan db:seed --class="Modules\BusinessProject\Database\Seeders\BcosDemoSeeder"  # demo data cho toàn bộ luồng BCOS (3 project, 8 workspace)

# Module scaffolding
php artisan module:make ModuleName
php artisan migration:generate --fresh
```

## Architecture Overview

### Multi-Tenancy

All user data is scoped to an `Organization`. The `TenantContext` static singleton is hydrated by middleware (not the service container) and is read by all tenant-scoped queries. Organization is identified via a 4-layer detection: subdomain → header → authenticated user → session.

Base classes enforce tenant isolation:
- `app/Foundation/TenantAwareModel.php` — adds `organization_id` global scope
- `app/Foundation/TenantAwareJob.php` — restores tenant context in queue workers
- `app/Shared/Tenancy/` — TenantContext, Organization model, and traits

### RBAC

Eight roles (CEO, Sales, Ops, Marketing, HR, AI_Operator, System_Admin, Viewer) with 40+ permissions across 11 domains. `config/permissions.php` maps roles to visible sidebar modules — the UI is rendered from this config, not hardcoded.

### Module System (NWIDART)

Feature modules live in `Modules/`. 37 modules are implemented (not stubs — this section used to say otherwise). Generate new modules with `php artisan module:make`.

### Module Mapping vs. `spec/nghiencuu/hesinhthai.docx` (THUCHOCVN Blueprint)

The blueprint document defines the vision/methodology/architecture (Khung năng lực, Bản sao số năng lực, TOS/TIE/TIC layers, 9-stage consulting delivery). Terminology differs between the doc and the code — this maps them.

**Core modules matching a blueprint concept** (keep, terminology differs from doc):
- `Assessment` → Khung năng lực (ch.8) + Bản sao số năng lực (ch.9), via `WorkforceProfile`/Passport models
- `Survey` → Khảo sát & chẩn đoán doanh nghiệp (ch.7)
- `JobTitle` → "Vị trí việc làm" (ch.8, Tầng 1)
- `BusinessProject` → quy trình triển khai 9 giai đoạn (Phần IV), via its 8-workspace engine
- `BusinessBlueprint`, `BusinessSolution`, `OrganizationSolution`, `SolutionCatalog` → mô hình "một phương pháp – nhiều giải pháp theo lĩnh vực" (ch.20)
- `Sop`, `WorkflowAutomation` → TOS, hệ điều hành nội bộ (ch.15)
- `KcCategory`, `KcItem` → quản trị tri thức (ch.15 #4)
- `AiCopilot` → hệ sinh thái AI (ch.18) — doc describes 6 specialized assistants, code has one shared copilot
- `Report`, `ActivityLog` → điều phối & hỗ trợ ra quyết định (ch.12)
- `Project`, `Task` → quản trị dự án (ch.15 #2)
- `Deployment` → nhân bản giải pháp theo lĩnh vực (ch.20)
- `Employee`, `Department`, `Branch`, `OrgChart`, `RoleScope` → mô hình tổ chức (7.4.2)
- `PerformanceReview`, `KpiGoal` → đánh giá hiệu quả làm việc (7.4.7, 8.8)

**Modules with no counterpart in the blueprint** (likely a separate commercial product line, not core THUCHOCVN Blueprint — confirm scope before extending): `Lead`, `LeadSource`, `LeadPipelineStage` (sales CRM), `Customer`, `Marketplace`, `JobPosting`, `Recruitment` (job marketplace), `Leave` (HR ops), `Subscription` (SaaS billing).

**Blueprint concepts with no module yet**: explicit TOS/TIE/TIC architecture layer boundaries (logic currently spread across modules with no namespace separation); multi-assistant AI split (ch.18); "GPHI" IP asset (line ~2401); standardized external-system integration layer (ch.19); "cơ sở giáo dục"/"cơ quan quản lý" as ecosystem roles (only 8 internal roles exist today).
