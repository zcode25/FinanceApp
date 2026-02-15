<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Prepare data mapping for subscription_transactions
        $mapping = [
            'starter' => 1,
            'monthly' => 2,
            'yearly' => 3,
            'lifetime' => 4,
        ];

        // 2. Temporarily disable foreign keys if any (though currently it's just a string column)
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->string('plan_id_old')->nullable()->after('plan_id');
        });

        // 3. Backup old plan_id
        DB::table('subscription_transactions')->update(['plan_id_old' => DB::raw('plan_id')]);

        // 4. Transform plan_id to temporary integer strings to avoid cast issues during type change
        foreach ($mapping as $old => $new) {
            DB::table('subscription_transactions')
                ->where('plan_id', $old)
                ->update(['plan_id' => (string) $new]);
        }

        // 5. Modify plans table ID to bigIncrements
        // Since we can't easily change a primary key string to bigIncrements in one go with raw SQL sometimes,
        // we'll recreate the table or use a safer approach.

        // Safer approach: Drop primary key, change type, set primary back.
        Schema::table('plans', function (Blueprint $table) {
            $table->dropPrimary('id');
        });

        // Map plans data before changing ID type
        foreach ($mapping as $old => $new) {
            DB::table('plans')->where('id', $old)->update(['id' => (string) $new]);
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true)->change();
        });

        // 6. Modify subscription_transactions.plan_id to unsignedBigInteger
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->change();
        });

        // 7. Add foreign key
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });

        // 8. Clean up
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->dropColumn('plan_id_old');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->string('plan_id')->nullable()->change();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropPrimary('id');
            $table->string('id')->primary()->change();
        });

        $reverseMapping = [
            1 => 'starter',
            2 => 'monthly',
            3 => 'yearly',
            4 => 'lifetime',
        ];

        foreach ($reverseMapping as $new => $old) {
            DB::table('plans')->where('id', (string) $new)->update(['id' => $old]);
            DB::table('subscription_transactions')->where('plan_id', (string) $new)->update(['plan_id' => $old]);
        }
    }
};
