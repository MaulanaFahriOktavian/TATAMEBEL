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
        Schema::create('shipping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('courier', 100);
            $table->string('tracking_number')->nullable();
            $table->text('shipping_address');
            $table->timestamp('shipped_at')->nullable();
            $table->date('estimated_arrival')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('status', 20)->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('workshop_id');
            $table->index('tracking_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping');
    }
};
