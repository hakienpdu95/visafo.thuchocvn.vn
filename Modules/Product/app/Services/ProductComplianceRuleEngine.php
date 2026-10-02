<?php

namespace Modules\Product\Services;

use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Product\Models\PartnerProduct;
use Modules\Product\Models\Product;

/**
 * Ánh xạ nhóm hàng (categories.code) của sản phẩm -> giấy tờ pháp lý bắt buộc
 * (document_master_types.code). Mỗi nhóm có bộ quy tắc riêng, không dùng chung.
 *
 * Căn cứ pháp lý (hard-code theo văn bản quy phạm — KHÔNG tự suy diễn luật):
 * - Nghị định 15/2018/NĐ-CP, Điều 4: thực phẩm đã qua chế biến bao gói sẵn, phụ gia thực phẩm phải
 *   TỰ CÔNG BỐ; Điều 5/Điều 7: hồ sơ công bố kèm Phiếu kết quả kiểm nghiệm ATTP trong thời hạn 12 tháng.
 *   Điều 6: thực phẩm bảo vệ sức khỏe/dinh dưỡng y học phải ĐĂNG KÝ BẢN CÔNG BỐ.
 * - Luật Thú y 2015 + Thông tư 25/2016/TT-BNNPTNT, 26/2016/TT-BNNPTNT: động vật, sản phẩm động vật
 *   và thủy sản tươi sống phải có Giấy chứng nhận kiểm dịch (`supplier_vet`).
 * - Hướng dẫn 02/HD-BCĐ (Hà Nội) và tương đương: rau củ quả tươi truy xuất nguồn gốc qua chứng nhận
 *   VietGAP/GlobalGAP (`supplier_vietgap`) hoặc kết quả phân tích đất/nước vùng trồng
 *   (`supplier_soil_water_test`).
 *
 * Nhóm `beverages_water` và nhóm cũ `fresh_food` (chưa tách thịt/rau) không có quy tắc.
 */
class ProductComplianceRuleEngine
{
    private const PROCESSED_GROUPS = [
        'processed_food',
        'prepackaged_food',
        'additives_spices',
        'functional_fortified_food',
    ];

    /**
     * @return ComplianceRequirement[]
     */
    public function requirementsFor(Product $product): array
    {
        $code = $product->category?->code;

        if (in_array($code, self::PROCESSED_GROUPS, true)) {
            return [
                new ComplianceRequirement(
                    label: 'Hồ sơ công bố sản phẩm (Tự công bố / Đăng ký)',
                    documentTypeCodes: ['product_declaration'],
                    legalBasis: 'Nghị định 15/2018/NĐ-CP, Điều 4/Điều 6',
                ),
                new ComplianceRequirement(
                    label: 'Phiếu kiểm nghiệm / Báo cáo kết quả phân tích sản phẩm (định kỳ)',
                    documentTypeCodes: ['product_test_report'],
                    legalBasis: 'Nghị định 15/2018/NĐ-CP, Điều 5/Điều 7',
                ),
            ];
        }

        return match ($code) {
            'fresh_meat_seafood' => [
                new ComplianceRequirement(
                    label: 'Giấy chứng nhận kiểm dịch thú y',
                    documentTypeCodes: ['supplier_vet'],
                    legalBasis: 'Luật Thú y 2015; Thông tư 25/2016/TT-BNNPTNT, 26/2016/TT-BNNPTNT',
                ),
            ],

            'fresh_produce' => [
                new ComplianceRequirement(
                    label: 'Chứng nhận VietGAP/GlobalGAP hoặc kết quả phân tích đất/nước vùng trồng',
                    documentTypeCodes: ['supplier_vietgap', 'supplier_soil_water_test'],
                    legalBasis: 'Hướng dẫn 02/HD-BCĐ',
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
