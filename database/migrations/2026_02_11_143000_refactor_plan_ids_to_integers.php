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
        if (!Schema::hasColumn('subscription_transactions', 'plan_id_old')) {
            Schema::table('subscription_transactions', function (Blueprint $table) {
                $table->string('plan_id_old')->nullable()->after('plan_id');
            });
        }

        // 3. Backup old plan_id
        DB::table('subscription_transactions')->update(['plan_id_old' => DB::raw('plan_id')]);

        // 4. Transform plan_id to temporary integer strings to avoid cast issues during type change
        foreach ($mapping as $old => $new) {
            DB::table('subscription_transactions')
                ->where('plan_id', $old)
                ->update(['plan_id' => (string) $new]);
        }

        // 5. Modify plans table ID to bigIncrements using table reconstruction
        // This is safer for hosting environments that struggle with complex ALTER TABLE changes
        if (Schema::hasTable('plans')) {
            if (Schema::hasTable('plans_old')) {
                Schema::dropIfExists('plans_old');
            }
            Schema::rename('plans', 'plans_old');
        }

        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->integer('price')->default(0);
                $table->timestamps();
            });
        }

        // Migrate plans data with New IDs
        foreach ($mapping as $old => $new) {
            $oldPlan = DB::table('plans_old')->where('id', $old)->first();
            if ($oldPlan) {
                DB::table('plans')->insert([
                    'id' => $new,
                    'name' => $oldPlan->name,
                    'price' => $oldPlan->price,
                    'created_at' => $oldPlan->created_at,
                    'updated_at' => $oldPlan->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('plans_old');

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
        });

        $reverseMapping = [
            1 => 'starter',
            2 => 'monthly',
            3 => 'yearly',
            4 => 'lifetime',
        ];

        Schema::rename('plans', 'plans_old');

        Schema::create('plans', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('price')->default(0);
            $table->timestamps();
        });

        foreach ($reverseMapping as $new => $old) {
            $oldPlan = DB::table('plans_old')->where('id', $new)->first();
            if ($oldPlan) {
                DB::table('plans')->insert([
                    'id' => $old,
                    'name' => $oldPlan->name,
                    'price' => $oldPlan->price,
                    'created_at' => $oldPlan->created_at,
                    'updated_at' => $oldPlan->updated_at,
                ]);
            }
        }

        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->string('plan_id')->nullable()->change();
        });

        foreach ($reverseMapping as $new => $old) {
            DB::table('subscription_transactions')->where('plan_id', (string) $new)->update(['plan_id' => $old]);
        }

        Schema::dropIfExists('plans_old');
    }
};
