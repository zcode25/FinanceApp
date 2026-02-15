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
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('user_id')->constrained('promos')->nullOnDelete();
            $table->string('plan_id')->nullable()->after('external_id');
            $table->decimal('gross_amount', 15, 2)->nullable()->after('amount');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('gross_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'plan_id', 'gross_amount', 'discount_amount']);
        });
    }
};
