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
        Schema::table('transactions', function (Blueprint $table) {
            // Composite indexes for common dashboard/analysis filters
            $table->index(['user_id', 'date', 'is_active'], 'transactions_user_date_active_index');
            $table->index(['user_id', 'type', 'is_active'], 'transactions_user_type_active_index');
            $table->index(['user_id', 'category_id', 'is_active'], 'transactions_user_cat_active_index');
            $table->index(['user_id', 'wallet_id', 'is_active'], 'transactions_user_wallet_active_index');
            
            // Single column index for range queries on date
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_date_active_index');
            $table->dropIndex('transactions_user_type_active_index');
            $table->dropIndex('transactions_user_cat_active_index');
            $table->dropIndex('transactions_user_wallet_active_index');
            $table->dropIndex(['date']);
        });
    }
};
