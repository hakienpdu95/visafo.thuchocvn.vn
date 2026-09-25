<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GROUP_BY_CODE = [
        'internal_business_registration' => 'legal_facility',
        'supplier_business_registration' => 'legal_facility',
        'facility_attp'                  => 'legal_facility',
        'supplier_attp'                  => 'legal_facility',
        'facility_commitment'            => 'legal_facility',
        'supplier_commitment'            => 'legal_facility',
        'internal_haccp'                 => 'legal_facility',
        'supplier_gmp'                   => 'legal_facility',
        'supplier_vietgap'               => 'legal_facility',
        'internal_records_authorization' => 'legal_facility',

        'product_declaration' => 'traceability',
        'supplier_vet'        => 'traceability',
        'internal_soil_test'  => 'traceability',
        'internal_water_test' => 'traceability',
        'supplier_ocop'       => 'traceability',
        'daily_invoice'       => 'traceability',

        'personnel_health'            => 'personnel',
        'personnel_periodic_health'   => 'personnel',
        'personnel_training'          => 'personnel',
        'personal_id_card'            => 'personnel',
        'internal_staff_list'         => 'personnel',
        'internal_quality_assignment' => 'personnel',

        'log_3_steps'           => 'monitoring_logs',
        'log_sample'            => 'monitoring_logs',
        'log_chemical'          => 'monitoring_logs',
        'internal_flow_diagram' => 'monitoring_logs',

        'internal_price_list'         => 'commercial',
        'internal_capability_profile' => 'commercial',
        'internal_reference_contract' => 'commercial',
    ];

    private const LEGACY_TAB_BY_CODE = [
        'internal_business_registration' => 'legal',
        'internal_records_authorization' => 'legal',
        'facility_commitment'            => 'legal',
        'facility_attp'                  => 'operation',
        'internal_haccp'                 => 'operation',
        'internal_soil_test'             => 'operation',
        'internal_water_test'            => 'operation',
        'internal_flow_diagram'          => 'operation',
        'log_3_steps'                    => 'operation',
        'log_sample'                     => 'operation',
        'log_chemical'                   => 'operation',
        'internal_staff_list'            => 'hr',
        'personnel_training'             => 'hr',
        'personnel_periodic_health'      => 'hr',
        'internal_quality_assignment'    => 'hr',
        'internal_price_list'            => 'commercial',
        'internal_capability_profile'    => 'commercial',
        'internal_reference_contract'    => 'commercial',
    ];

    private const LEGACY_GROUP_BY_CODE = [
        'facility_attp'                  => 'attp_quality',
        'internal_haccp'                 => 'attp_quality',
        'internal_soil_test'             => 'attp_quality',
        'internal_water_test'            => 'attp_quality',
        'internal_flow_diagram'          => 'attp_quality',
        'internal_capability_profile'    => 'legal_facility',
        'internal_reference_contract'    => 'legal_facility',
        'supplier_business_registration' => 'traceability',
        'supplier_attp'                  => 'traceability',
        'supplier_commitment'            => 'traceability',
        'supplier_vietgap'               => 'traceability',
        'supplier_gmp'                   => 'traceability',
        'personal_id_card'               => 'traceability',
    ];

    public function up(): void
    {
        foreach (self::GROUP_BY_CODE as $code => $group) {
            DB::table('document_master_types')->where('code', $code)->update(['document_group' => $group]);
        }

        DB::table('document_master_types')->where('document_group', 'attp_quality')->update(['document_group' => 'legal_facility']);
        DB::table('sales_package_items')->where('document_group', 'attp_quality')->update(['document_group' => 'legal_facility']);

        Schema::table('document_master_types', function (Blueprint $table) {
            $table->dropIndex('idx_document_master_types_internal_tab_group');
            $table->dropColumn('internal_tab_group');
        });
    }

    public function down(): void
    {
        Schema::table('document_master_types', function (Blueprint $table) {
            $table->string('internal_tab_group', 20)->nullable()->after('document_group');
            $table->index('internal_tab_group', 'idx_document_master_types_internal_tab_group');
        });

        foreach (self::LEGACY_TAB_BY_CODE as $code => $tab) {
            DB::table('document_master_types')->where('code', $code)->update(['internal_tab_group' => $tab]);
        }

        foreach (self::LEGACY_GROUP_BY_CODE as $code => $group) {
            DB::table('document_master_types')->where('code', $code)->update(['document_group' => $group]);
        }
    }
};
