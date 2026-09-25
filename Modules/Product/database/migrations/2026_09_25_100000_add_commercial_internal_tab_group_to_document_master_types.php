<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const MOVED_CODES = ['internal_capability_profile', 'internal_reference_contract'];

    private const PRICE_LIST_CODE = 'internal_price_list';

    public function up(): void
    {
        DB::table('document_master_types')
            ->whereIn('code', self::MOVED_CODES)
            ->update(['internal_tab_group' => 'commercial', 'updated_at' => now()]);

        if (DB::table('document_master_types')->where('code', self::PRICE_LIST_CODE)->exists()) {
            return;
        }

        DB::table('document_master_types')->insert([
            'id'                      => strtolower((string) Str::ulid()),
            'code'                    => self::PRICE_LIST_CODE,
            'name'                    => 'Bảng giá / Báo giá tiêu chuẩn',
            'document_group'          => 'commercial',
            'internal_tab_group'      => 'commercial',
            'applicable_to'           => json_encode(['internal']),
            'is_required_issue_date'  => true,
            'is_required_expiry_date' => true,
            'has_expiration_date'     => true,
            'has_issue_place'         => false,
            'is_transactional'        => false,
            'default_validity_months' => 12,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('document_master_types')
            ->whereIn('code', self::MOVED_CODES)
            ->update(['internal_tab_group' => 'legal', 'updated_at' => now()]);

        $priceListId = DB::table('document_master_types')->where('code', self::PRICE_LIST_CODE)->value('id');
        if ($priceListId && ! DB::table('compliance_documents')->where('document_master_type_id', $priceListId)->exists()) {
            DB::table('document_master_types')->where('id', $priceListId)->delete();
        }
    }
};
