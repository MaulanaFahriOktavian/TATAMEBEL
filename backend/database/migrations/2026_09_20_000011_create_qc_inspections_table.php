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
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('inspected_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();

            $table->index('workshop_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('inspected_by');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->foreign('qc_inspection_id')
                ->references('id')
                ->on('qc_inspections')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['qc_inspection_id']);
        });

        Schema::dropIfExists('qc_inspections');
    }
};
