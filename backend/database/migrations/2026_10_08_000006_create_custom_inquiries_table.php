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
        Schema::create('custom_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('project_type'); // e.g. 'Residensial (Hunian Pribadi)', 'Komersial & Hospitality', etc.
            $table->string('timeline'); // e.g. 'Segera (< 1 bulan)', '1–3 Bulan', etc.
            $table->text('description'); // project dimensions, preferences, description
            $table->string('status', 30)->default('pending'); // pending, reviewed, contacted, in_progress, closed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_inquiries');
    }
};
