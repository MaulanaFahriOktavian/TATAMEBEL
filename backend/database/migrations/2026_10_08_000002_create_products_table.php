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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('category_name')->nullable();
            $table->string('badge')->nullable(); // e.g. 'Diskon 22%', 'Karya Tangan', 'Karya Unggulan', 'Terlaris'
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('original_price', 15, 2)->nullable();
            $table->unsignedSmallInteger('discount_percent')->nullable();
            $table->string('wood_type')->nullable(); // e.g. 'Kayu Jati Grade A', 'Jati Solid Oven'
            $table->unsignedInteger('stock')->default(10);
            $table->json('colors')->nullable(); // e.g. ["#44403c", "#1c1917", "#a8a29e"]
            $table->text('image_url');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category_id');
            $table->index('slug');
            $table->index('is_featured');
            $table->index('is_active');
            $table->index('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
