<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jodit_drafts')) {
            return;
        }

        Schema::create('jodit_drafts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignUlid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('context_type')->nullable()->comment('Loại entity sẽ nhận media sau khi save');
            $table->ulid('context_id')->nullable()->comment('ID entity sẽ nhận media sau khi save');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['organization_id', 'user_id']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('jodit_drafts');
    }
};