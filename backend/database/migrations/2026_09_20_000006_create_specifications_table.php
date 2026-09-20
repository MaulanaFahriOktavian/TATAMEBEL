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
        Schema::create('specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('depth', 10, 2)->nullable();
            $table->string('dimension_unit', 20)->default('cm');
            $table->string('material')->nullable();
            $table->string('wood_grade', 100)->nullable();
            $table->string('finishing')->nullable();
            $table->string('color', 100)->nullable();
            $table->string('fabric')->nullable();
            $table->text('design_reference')->nullable();
            $table->text('special_request')->nullable();
            $table->text('production_note')->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_item_id', 'version']);
            $table->index('workshop_id');
            $table->index('order_item_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specifications');
    }
};
