<?php

namespace App\Console\Commands;

use App\Support\Permissions\PermissionUpgrader;
use Illuminate\Console\Command;

class UpgradePermissionsV2Command extends Command
{
    protected $signature = 'permission:upgrade-v2 {--dry-run : Chỉ thống kê, không ghi DB}';

    protected $description = 'Cấp quyền chi tiết (view_all/view_own/create/update/delete) tương ứng quyền .view/.manage cũ cho Role & User — không xóa quyền cũ';

    public function handle(PermissionUpgrader $upgrader): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun) {
            $created = $upgrader->ensurePermissionsExist();
            $this->line("Tạo mới {$created} permission chi tiết.");
        }

        $result = $upgrader->upgrade($dryRun);

        $this->info(($dryRun ? '[dry-run] ' : '') . "Role cần/đã bổ sung quyền: {$result['roles']} — User (direct) cần/đã bổ sung: {$result['users']}.");

        return self::SUCCESS;
    }
}
