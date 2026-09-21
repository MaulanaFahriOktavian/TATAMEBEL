<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qc_items', function (Blueprint $table) {
            $table->foreignId('order_item_id')
                ->nullable()
                ->after('qc_inspection_id')
                ->constrained('order_items')
                ->nullOnDelete();

            $table->string('status', 20)->nullable()->default(null)->change();

            $table->index('order_item_id');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('qc_defect_id')
                ->nullable()
                ->after('qc_inspection_id')
                ->constrained('qc_defects')
                ->nullOnDelete();

            $table->index('qc_defect_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['qc_defect_id']);
            $table->dropIndex(['qc_defect_id']);
            $table->dropColumn('qc_defect_id');
        });

        Schema::table('qc_items', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
            $table->dropIndex(['order_item_id']);
            $table->dropColumn('order_item_id');
            $table->string('status', 20)->default('PASS')->change();
        });
    }
};
