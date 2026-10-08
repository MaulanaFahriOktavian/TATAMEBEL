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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name'); // e.g. 'Finishing', 'Ukuran'
            $table->string('value'); // e.g. 'Natural Teak', 'Smoked Charcoal'
            $table->string('sku')->nullable();
            $table->decimal('price_adjustment', 15, 2)->default(0.00);
            $table->unsignedInteger('stock')->default(10);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
