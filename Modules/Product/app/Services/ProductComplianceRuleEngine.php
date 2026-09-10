<?php

namespace Modules\Product\Services;

use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;

/**
 * Ánh xạ nhóm thực phẩm (categories.code) của sản phẩm -> giấy tờ pháp lý bắt buộc
 * (document_master_types.code).
 *
 * Căn cứ pháp lý (hard-code theo văn bản quy phạm — KHÔNG tự suy diễn luật):
 * - Nghị định 15/2018/NĐ-CP, Điều 4: thực phẩm bao gói sẵn, phụ gia thực phẩm phải TỰ CÔNG BỐ.
 *   Điều 6: thực phẩm bảo vệ sức khỏe/dinh dưỡng y học phải ĐĂNG KÝ BẢN CÔNG BỐ.
 *   => Cả hai đều thể hiện qua hồ sơ `product_declaration` trong document_master_types.
 * - Luật Thú y 2015 + Thông tư 25/2016/TT-BNNPTNT: sản phẩm động vật tươi sống (thịt, trứng...)
 *   vận chuyển/kinh doanh phải có Giấy chứng nhận kiểm dịch thú y (`supplier_vet`).
 * - Hướng dẫn 02/HD-BCĐ (Hà Nội) và tương đương: rau củ quả tươi khuyến nghị truy xuất nguồn gốc
 *   qua chứng nhận VietGAP/GlobalGAP (`supplier_vietgap`) — chấp nhận thay thế cho `supplier_vet`
 *   với nhóm fresh_food (thú y chỉ áp dụng động vật, VietGAP áp dụng rau củ quả).
 *
 * Nhóm `processed_food` và `beverages_water` chưa có quy tắc giấy tờ cụ thể — để trống
 * thay vì tự suy diễn, chờ căn cứ pháp lý được xác nhận.
 */
class ProductComplianceRuleEngine
{
    /**
     * @return ComplianceRequirement[]
     */
    public function requirementsFor(Product $product): array
    {
        return match ($product->category?->code) {
            'prepackaged_food',
            'additives_spices',
            'functional_fortified_food' => [
                new ComplianceRequirement(
                    label: 'Hồ sơ công bố sản phẩm',
                    documentTypeCodes: ['product_declaration'],
                    legalBasis: 'Nghị định 15/2018/NĐ-CP, Điều 4/Điều 6',
                ),
            ],

            'fresh_food' => [
                new ComplianceRequirement(
                    label: 'Giấy kiểm dịch thú y hoặc chứng nhận VietGAP/GlobalGAP',
                    documentTypeCodes: ['supplier_vet', 'supplier_vietgap'],
                    legalBasis: 'Luật Thú y 2015; Thông tư 25/2016/TT-BNNPTNT',
                ),
            ],

            default => [],
        };
    }

    /**
     * @return ComplianceRequirementResult[]
     */
    public function evaluate(PartnerProduct $partnerProduct): array
    {
        $product = $partnerProduct->product;
        if ($product === null) {
            return [];
        }

        $requirements = $this->requirementsFor($product);
        if (empty($requirements)) {
            return [];
        }

        $validDocuments = $partnerProduct->documents
            ->filter(fn ($d) => $d->status === ComplianceDocumentStatus::Active && ! $d->isExpired());

        return array_map(
            function (ComplianceRequirement $requirement) use ($validDocuments) {
                $match = $validDocuments->first(
                    fn ($d) => in_array($d->documentType?->code, $requirement->documentTypeCodes, true)
                );

                return new ComplianceRequirementResult($requirement, $match !== null, $match);
            },
            $requirements
        );
    }

    /**
     * @param ComplianceRequirementResult[] $results
     */
    public function missing(array $results): array
    {
        return array_values(array_filter($results, fn (ComplianceRequirementResult $r) => ! $r->satisfied));
    }

    public function isReady(PartnerProduct $partnerProduct): bool
    {
        return empty($this->missing($this->evaluate($partnerProduct)));
    }
}
