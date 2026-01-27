<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('exchange_rate', 10, 4)->nullable()->after('currency');
            $table->timestamp('exchange_rate_date')->nullable()->after('exchange_rate');
            $table->decimal('amount_in_base_currency', 15, 2)->nullable()->after('exchange_rate_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'exchange_rate_date', 'amount_in_base_currency']);
        });
    }
};
