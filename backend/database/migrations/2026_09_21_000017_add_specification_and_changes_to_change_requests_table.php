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
        Schema::table('change_requests', function (Blueprint $table) {
            $table->foreignId('order_item_id')->nullable()->after('order_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('specification_id')->nullable()->after('order_item_id')->constrained('specifications')->nullOnDelete();
            $table->unsignedInteger('current_version')->nullable()->after('specification_id');
            $table->json('requested_changes')->nullable()->after('description');
            $table->text('review_note')->nullable()->after('approved_at');
            $table->foreignId('user_id')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();

            $table->index('order_item_id');
            $table->index('specification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
            $table->dropForeign(['specification_id']);
            $table->dropForeign(['user_id']);

            $table->dropIndex(['order_item_id']);
            $table->dropIndex(['specification_id']);

            $table->dropColumn([
                'order_item_id',
                'specification_id',
                'current_version',
                'requested_changes',
                'review_note',
                'user_id',
            ]);
        });
    }
};
