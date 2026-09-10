<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;
use Modules\Product\Models\DocumentMasterType;
use Modules\Product\Models\PartnerProductCompliance;
use Modules\Product\Models\ProductCompliance;
use Modules\Vendor\Models\VendorCertificate;

class MigrateOldCompliancesCommand extends Command
{
    protected $signature = 'app:migrate-old-compliances';

    protected $description = 'Chuyển dữ liệu từ vendor_certificates/product_compliances/partner_product_compliances (3 bảng cũ, sắp bị xoá) sang compliance_documents (bảng polymorphic mới)';

    private const VENDOR_CERT_TYPE_MAP = [
        'business_registration' => 'supplier_business_registration',
        'food_safety'           => 'supplier_attp',
        'gmp'                   => 'supplier_gmp',
    ];

    public function handle(): int
    {
        $hasAnyOldTable = Schema::hasTable('vendor_certificates')
            || Schema::hasTable('product_compliances')
            || Schema::hasTable('partner_product_compliances');

        if (!$hasAnyOldTable) {
            $this->warn('Không còn bảng cũ nào tồn tại — có thể đã rework xong hoặc chưa từng có. Bỏ qua.');
            return self::SUCCESS;
        }

        $documentTypeIds = DocumentMasterType::query()->pluck('id', 'code');

        $migrated = [
            'vendor_certificates'         => Schema::hasTable('vendor_certificates') ? $this->migrateVendorCertificates($documentTypeIds) : null,
            'product_compliances'         => Schema::hasTable('product_compliances') ? $this->migrateProductCompliances($documentTypeIds) : null,
            'partner_product_compliances' => Schema::hasTable('partner_product_compliances') ? $this->migratePartnerProductCompliances($documentTypeIds) : null,
        ];

        foreach ($migrated as $table => $count) {
            if ($count === null) {
                $this->line("  - $table: bảng không tồn tại, bỏ qua.");
            } else {
                $this->info("  ✓ $table: $count dòng đã migrate.");
            }
        }

        return self::SUCCESS;
    }

    private function migrateVendorCertificates(\Illuminate\Support\Collection $documentTypeIds): int
    {
        $count = 0;

        foreach (DB::table('vendor_certificates')->get() as $row) {
            $code = self::VENDOR_CERT_TYPE_MAP[$row->certificate_type] ?? null;

            if ($code === null || !$documentTypeIds->has($code)) {
                $this->warn("  ⚠ Bỏ qua vendor_certificates#{$row->id}: certificate_type '{$row->certificate_type}' không map được sang document_master_types.");
                continue;
            }

            $document = ComplianceDocument::create([
                'document_master_type_id' => $documentTypeIds->get($code),
                'documentable_type'       => 'vendor',
                'documentable_id'         => $row->vendor_id,
                'document_number'         => $row->certificate_number,
                'issue_date'              => $row->issue_date,
                'expiration_date'         => $row->expiry_date,
                'issued_by'               => $row->issued_by,
                'status'                  => $row->is_active
                    ? ComplianceDocumentStatus::Active->value
                    : ComplianceDocumentStatus::Superseded->value,
            ]);

            $this->reassociateMedia(VendorCertificate::class, 'vendor_certificate', $row->id, $document);
            $count++;
        }

        return $count;
    }

    private function migrateProductCompliances(\Illuminate\Support\Collection $documentTypeIds): int
    {
        $count = 0;

        foreach (DB::table('product_compliances')->get() as $row) {
            if (!$documentTypeIds->contains($row->document_type_id)) {
                $this->warn("  ⚠ Bỏ qua product_compliances#{$row->id}: document_type_id {$row->document_type_id} không tồn tại.");
                continue;
            }

            $document = ComplianceDocument::create([
                'document_master_type_id' => $row->document_type_id,
                'documentable_type'       => 'product',
                'documentable_id'         => $row->product_id,
                'document_number'         => $row->document_number,
                'classification_grade'    => $row->classification_grade,
                'issue_date'              => $row->issue_date,
                'expiration_date'         => $row->expiration_date,
                'status'                  => $row->status,
            ]);

            $this->reassociateMedia(ProductCompliance::class, 'product_compliance', $row->id, $document);
            $count++;
        }

        return $count;
    }

    private function migratePartnerProductCompliances(\Illuminate\Support\Collection $documentTypeIds): int
    {
        $count = 0;

        foreach (DB::table('partner_product_compliances')->get() as $row) {
            if (!$documentTypeIds->contains($row->document_type_id)) {
                $this->warn("  ⚠ Bỏ qua partner_product_compliances#{$row->id}: document_type_id {$row->document_type_id} không tồn tại.");
                continue;
            }

            $document = ComplianceDocument::create([
                'document_master_type_id' => $row->document_type_id,
                'documentable_type'       => 'partner_product',
                'documentable_id'         => $row->partner_product_id,
                'document_number'         => $row->document_number,
                'issue_date'              => $row->issue_date,
                'expiration_date'         => $row->expiration_date,
                'status'                  => $row->status,
            ]);

            $this->reassociateMedia(PartnerProductCompliance::class, null, $row->id, $document);
            $count++;
        }

        return $count;
    }

    /**
     * Trỏ lại media Spatie từ model cũ sang ComplianceDocument mới (không copy file vật lý).
     * model_type của media cũ có thể là FQCN hoặc morph alias tuỳ model có được đăng ký
     * Relation::morphMap() lúc media được tạo hay không — kiểm tra cả 2 dạng cho chắc.
     */
    private function reassociateMedia(string $oldModelClass, ?string $oldModelAlias, string $oldModelId, ComplianceDocument $document): void
    {
        $modelTypes = array_filter([$oldModelClass, $oldModelAlias]);

        DB::table('media')
            ->whereIn('model_type', $modelTypes)
            ->where('model_id', $oldModelId)
            ->update([
                'model_type' => $document->getMorphClass(),
                'model_id'   => $document->id,
            ]);
    }
}
