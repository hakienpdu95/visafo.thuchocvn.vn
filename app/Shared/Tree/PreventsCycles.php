<?php

namespace App\Shared\Tree;

/**
 * SRS01-FR-STR-001/006 (GAP_ANALYSIS_v1.0.md §3.3 STR-01): chống cycle THẬT khi
 * move một node cây self-referential (parent_id) — trước đây chỉ chặn ở
 * controller/UI (loại option trong dropdown), không có guard ở tầng service/Action
 * nên vẫn có thể tạo cycle qua API/import. Dùng cho model có cột `parent_id` và
 * quan hệ tự tham chiếu (Department, Branch, Position...).
 */
trait PreventsCycles
{
    /** Override ở model con nếu cột self-ref không phải `parent_id` (VD Position dùng `manager_position_id`). */
    protected function cycleParentColumn(): string
    {
        return 'parent_id';
    }

    /**
     * true nếu gán $newParentId làm cha của bản ghi hiện tại sẽ tạo cycle
     * (chính nó, hoặc bất kỳ node nào trong chuỗi cha hiện tại là con/cháu của nó).
     */
    public function wouldCreateCycle(?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if (!$this->exists) {
            // Bản ghi mới (chưa có id) không thể có descendant -> không thể tạo cycle.
            return false;
        }

        if ($newParentId === $this->getKey()) {
            return true;
        }

        $column    = $this->cycleParentColumn();
        $visited   = [$this->getKey() => true];
        $currentId = $newParentId;

        while ($currentId !== null) {
            if (isset($visited[$currentId])) {
                // Gặp lại 1 id đã đi qua (bao gồm chính $this) -> cycle.
                return true;
            }
            $visited[$currentId] = true;

            $currentId = static::withoutTenant()
                ->whereKey($currentId)
                ->value($column);
        }

        return false;
    }
}
