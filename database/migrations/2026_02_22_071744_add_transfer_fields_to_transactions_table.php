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
            $table->foreignId('target_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->decimal('fee', 15, 2)->default(0);
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE transactions MODIFY type ENUM('income', 'expense', 'transfer') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE transactions MODIFY type ENUM('income', 'expense') NOT NULL");
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['target_wallet_id']);
            $table->dropColumn(['target_wallet_id', 'fee']);
        });
    }
};
