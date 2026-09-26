<input type="hidden" name="permissions_submitted" value="1">

<div class="card bg-base-100 shadow-sm border border-base-200 mt-5">
    <div class="card-body">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
            <div>
                <h3 class="card-title text-base">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Phân quyền chi tiết
                </h3>
                <p class="text-xs text-base-content/50 mt-1">
                    Chọn vai trò để nạp quyền mặc định, sau đó tick/bỏ tick để tinh chỉnh riêng cho tài khoản này.
                    <span class="inline-flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-warning"></span>khác với vai trò gốc</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-sm" x-show="selectedRole" x-text="selectedRoleLabel"></span>
                <span class="badge badge-warning badge-sm" x-show="overrideCount() > 0" x-text="overrideCount() + ' tùy chỉnh'"></span>
                <button type="button" class="btn btn-ghost btn-xs" x-show="selectedRole && overrideCount() > 0"
                        @click="applyRoleDefaults(selectedRole)">Khôi phục theo vai trò</button>
            </div>
        </div>

        <div class="mb-3 p-2.5 bg-base-200/60 rounded-lg" x-show="sidebarModules.length > 0">
            <p class="text-xs text-base-content/50 font-medium mb-1.5">Hiển thị trong sidebar:</p>
            <div class="flex flex-wrap gap-1">
                <template x-for="mod in sidebarModules" :key="mod">
                    <span class="badge badge-xs badge-ghost border border-base-300 font-normal" x-text="mod"></span>
                </template>
            </div>
        </div>

        <div class="overflow-x-auto border border-base-200 rounded-lg">
            <table class="table table-sm w-full">
                <thead>
                    <tr class="text-xs text-base-content/60 bg-base-200/50">
                        <th class="min-w-48">Module</th>
                        <template x-for="col in permColumns" :key="col.key">
                            <th class="text-center whitespace-nowrap">
                                <label class="flex flex-col items-center gap-1 cursor-pointer">
                                    <span x-text="col.label"></span>
                                    <input type="checkbox" class="checkbox checkbox-xs"
                                           :checked="isColumnFull(col.key)" @change="toggleColumn(col.key, $event.target.checked)">
                                </label>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="mod in permModules" :key="mod.key">
                        <tr class="hover">
                            <td class="py-1.5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="checkbox checkbox-xs"
                                           :checked="isRowFull(mod)" @change="toggleRow(mod, $event.target.checked)">
                                    <span class="font-medium text-sm" x-text="mod.label"></span>
                                </label>
                            </td>
                            <template x-for="col in permColumns" :key="mod.key + col.key">
                                <td class="text-center py-1.5">
                                    <template x-if="mod.actions.includes(col.key)">
                                        <span class="relative inline-flex">
                                            <input type="checkbox" name="permissions[]" class="checkbox checkbox-sm checkbox-primary"
                                                   :value="mod.key + '.' + col.key"
                                                   :checked="hasPerm(mod.key + '.' + col.key)"
                                                   :disabled="!canGrant(mod.key + '.' + col.key)"
                                                   @change="setPerm(mod.key + '.' + col.key, $event.target.checked)">
                                            <span x-show="isOverride(mod.key + '.' + col.key)"
                                                  class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-warning"></span>
                                        </span>
                                    </template>
                                    <template x-if="!mod.actions.includes(col.key)">
                                        <span class="text-base-content/20">—</span>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @error('permissions')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
        @error('permissions.*')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
    </div>
</div>
