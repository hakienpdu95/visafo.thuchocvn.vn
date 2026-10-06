<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const NEW_TYPES = [
        'receipt_delivery_record' => ['Biên bản giao nhận / nhập kho lô hàng', false],
        'batch_residue_test'      => ['Phiếu kiểm tra dư lượng thuốc BVTV / test nhanh lô hàng', true],
    ];

    private const SHARED_CODES = ['product_test_report', 'supplier_test_report', 'supplier_vet'];

    public function up(): void
    {
        foreach (self::NEW_TYPES as $code => [$name, $hasIssuePlace]) {
            if (DB::table('document_master_types')->where('code', $code)->exists()) {
                continue;
            }

            DB::table('document_master_types')->insert([
                'id'                      => Str::lower((string) Str::ulid()),
                'code'                    => $code,
                'name'                    => $name,
                'document_group'          => 'traceability',
                'applicable_to'           => json_encode(['goods_receipt']),
                'is_required_issue_date'  => false,
                'is_required_expiry_date' => false,
                'has_expiration_date'     => false,
                'has_issue_place'         => $hasIssuePlace,
                'is_transactional'        => true,
                'default_validity_months' => null,
                'is_public'               => false,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        }

        foreach (DB::table('document_master_types')->whereIn('code', self::SHARED_CODES)->get(['id', 'applicable_to']) as $type) {
            $contexts = (array) json_decode((string) $type->applicable_to, true);
            if (! in_array('goods_receipt', $contexts, true)) {
                $contexts[] = 'goods_receipt';
                DB::table('document_master_types')->where('id', $type->id)->update(['applicable_to' => json_encode($contexts)]);
            }
        }
    }

    public function down(): void
    {
        DB::table('document_master_types')->whereIn('code', array_keys(self::NEW_TYPES))->delete();

        foreach (DB::table('document_master_types')->whereIn('code', self::SHARED_CODES)->get(['id', 'applicable_to']) as $type) {
            $contexts = array_values(array_diff((array) json_decode((string) $type->applicable_to, true), ['goods_receipt']));
            DB::table('document_master_types')->where('id', $type->id)->update(['applicable_to' => json_encode($contexts)]);
        }
    }
};
