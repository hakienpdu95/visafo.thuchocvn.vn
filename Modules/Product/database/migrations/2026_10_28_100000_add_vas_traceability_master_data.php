<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const GOODS_RECEIPT_CODES = ['supplier_vet', 'supplier_attp'];

    public function up(): void
    {
        if (! DB::table('categories')->where('code', 'dry_goods_spices')->exists()) {
            DB::table('categories')->insert([
                'id'          => Str::lower((string) Str::ulid()),
                'code'        => 'dry_goods_spices',
                'name'        => 'Gia vị & Đồ khô',
                'description' => null,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        if (! DB::table('document_master_types')->where('code', 'supplier_delivery_note')->exists()) {
            DB::table('document_master_types')->insert([
                'id'                      => Str::lower((string) Str::ulid()),
                'code'                    => 'supplier_delivery_note',
                'name'                    => 'Phiếu mua hàng / Phiếu xuất kho NCC',
                'document_group'          => 'traceability',
                'applicable_to'           => json_encode(['goods_receipt']),
                'is_required_issue_date'  => false,
                'is_required_expiry_date' => false,
                'has_expiration_date'     => false,
                'has_issue_place'         => false,
                'is_transactional'        => true,
                'default_validity_months' => null,
                'is_public'               => false,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        }

        foreach (DB::table('document_master_types')->whereIn('code', self::GOODS_RECEIPT_CODES)->get(['id', 'applicable_to']) as $type) {
            $contexts = (array) json_decode((string) $type->applicable_to, true);
            if (! in_array('goods_receipt', $contexts, true)) {
                $contexts[] = 'goods_receipt';
                DB::table('document_master_types')->where('id', $type->id)->update(['applicable_to' => json_encode($contexts)]);
            }
        }
    }

    public function down(): void
    {
        DB::table('document_master_types')->where('code', 'supplier_delivery_note')->delete();

        $attp = DB::table('document_master_types')->where('code', 'supplier_attp')->first(['id', 'applicable_to']);
        if ($attp !== null) {
            $contexts = array_values(array_diff((array) json_decode((string) $attp->applicable_to, true), ['goods_receipt']));
            DB::table('document_master_types')->where('id', $attp->id)->update(['applicable_to' => json_encode($contexts)]);
        }

        if (! DB::table('products')->whereIn('category_id', DB::table('categories')->where('code', 'dry_goods_spices')->select('id'))->exists()) {
            DB::table('categories')->where('code', 'dry_goods_spices')->delete();
        }
    }
};
