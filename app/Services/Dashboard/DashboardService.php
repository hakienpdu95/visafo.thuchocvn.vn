<?php

namespace App\Services\Dashboard;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\ActivityLog\Models\ActivityLog;

/**
 * Các thẻ KPI/action-feed dựa trên Lead/Task/Leave/Recruitment/WorkflowAutomation/Employee
 * đã bị gỡ cùng các module đó (cleanup/remove-non-competency-modules) — chỉ còn Recent
 * Activity (ActivityLog, giữ lại). kpi_cards/actionFeed() tạm rỗng cho tới khi có nguồn
 * dữ liệu mới (Competency/TCD) để build lại.
 */
class DashboardService
{
    public function getData(User $user): array
    {
        $orgId       = TenantContext::getOrganizationId();
        $primaryRole = $user->getRoleNames()->first() ?? RoleEnum::VIEWER->value;

        return [
            'kpi_cards'       => [],
            'action_feed'     => [],
            'recent_activity' => $this->recentActivity($orgId),
            'primary_role'    => $primaryRole,
        ];
    }

    // ── Recent Activity ───────────────────────────────────────────────────────

    private function recentActivity(?string $orgId): Collection
    {
        return ActivityLog::where(function ($q) use ($orgId) {
                $q->where('organization_id', $orgId)
                  ->orWhereNull('organization_id');
            })
            ->with('causer:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'description', 'subject_type', 'event', 'causer_id', 'causer_type', 'created_at', 'organization_id']);
    }
}
