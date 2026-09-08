<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('adverse_event_reports')) {
            return;
        }

        Schema::create('adverse_event_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('Tổ chức sở hữu');
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete()->comment('II. Tên sản phẩm');
            $table->foreignUlid('batch_id')->nullable()->constrained('batches')->nullOnDelete()->comment('II. Số lô — liên kết nếu xác định được');
            $table->string('lot_number_manual', 100)->nullable()->comment('II. Số lô — nhập tay khi không liên kết được batch');

            $table->string('company_name', 255)->comment('I. Tên công ty');
            $table->string('company_address', 500)->nullable()->comment('I. Địa chỉ công ty');
            $table->string('reporter_name', 150)->comment('I. Tên người thông báo');
            $table->string('reporter_title', 150)->nullable()->comment('I. Chức danh người thông báo');
            $table->string('reporter_phone', 20)->nullable()->comment('I. Số điện thoại');
            $table->string('reporter_fax', 20)->nullable()->comment('I. Fax');
            $table->string('reporter_email', 150)->nullable()->comment('I. Email');

            $table->text('ingredients_packaging')->nullable()->comment('II. Danh sách thành phần, dạng đóng gói');
            $table->string('product_form_purpose', 255)->nullable()->comment('II. Dạng sản phẩm/mục đích sử dụng');
            $table->string('manufacturer_origin', 255)->nullable()->comment('II. Tên công ty sản xuất/xuất xứ');

            $table->string('consumer_name', 150)->comment('III. Tên người sử dụng');
            $table->string('consumer_id_number', 50)->nullable()->comment('III. Số CMND hoặc hộ chiếu');
            $table->unsignedTinyInteger('consumer_age')->nullable()->comment('III. Tuổi');
            $table->string('consumer_gender', 10)->nullable()->comment('III. Giới tính — male | female | other');
            $table->string('consumer_nationality', 150)->nullable()->comment('III. Tôn giáo/Quốc tịch');
            $table->timestamp('onset_at')->nullable()->comment('III. Thời gian xuất hiện tác dụng bất lợi');
            $table->text('reaction_description')->comment('III. Mô tả tác dụng bất lợi');
            $table->string('time_since_last_use', 100)->nullable()->comment('III. Thời gian giữa lần dùng cuối và lúc xuất hiện tác dụng bất lợi');
            $table->text('usage_description')->nullable()->comment('III. Sản phẩm đã được sử dụng như thế nào');
            $table->boolean('was_hospitalized')->default(false)->comment('III. Có phải nhập viện không');
            $table->boolean('required_medical_treatment')->default(false)->comment('III. Có phải điều trị y tế không');
            $table->string('outcome', 20)->nullable()->comment('III. Kết quả — recovered | fatal | not_recovered | unknown');
            $table->date('outcome_date')->nullable()->comment('III. Ngày hồi phục/tử vong');
            $table->string('report_source', 30)->nullable()->comment('III. Nguồn cung cấp báo cáo — healthcare_professional | customer | other');
            $table->string('report_source_detail', 255)->nullable()->comment('III. Chi tiết nguồn báo cáo');

            $table->timestamp('received_at')->useCurrent()->index()->comment('Thời điểm công ty nhận được khiếu nại — mốc tính hạn 7 ngày báo cáo sơ bộ');
            $table->timestamp('submitted_to_authority_at')->nullable()->comment('Thời điểm đã nộp báo cáo cho Cục Quản lý Dược');
            $table->string('status', 20)->default('draft')->index()->comment('draft | submitted | closed');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'status'], 'idx_adverse_event_product_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adverse_event_reports');
    }
};
