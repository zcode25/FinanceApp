<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add category_id column (nullable first)
        Schema::table('budgets', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained('categories')->onDelete('cascade');
        });

        // 2. Data Migration
        // Find existing budgets and link them
        $budgets = DB::table('budgets')->get();

        foreach ($budgets as $budget) {
            $categoryName = $budget->category;
            $userId = $budget->user_id;

            // Find existing category
            $category = DB::table('categories')
                ->where('user_id', $userId)
                ->where('name', $categoryName)
                ->first();

            if ($category) {
                // Link to existing
                DB::table('budgets')
                    ->where('id', $budget->id)
                    ->update(['category_id' => $category->id]);
            } else {
                // Create new category if valid name, otherwise...
                // If the name matches standard types, use that type. Default to 'expense'.
                // Color? Random or 'bg-gray-500'.

                $newId = DB::table('categories')->insertGetId([
                    'user_id' => $userId,
                    'name' => $categoryName,
                    'type' => 'expense', // Assume expense for budgets
                    'color' => 'bg-gray-500',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                DB::table('budgets')
                    ->where('id', $budget->id)
                    ->update(['category_id' => $newId]);
            }
        }

        // 3. Make nullable -> not null, and drop old column
        Schema::table('budgets', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->string('category')->nullable();
        });

        // Restore data (reverse)
        $budgets = DB::table('budgets')->get();
        foreach ($budgets as $budget) {
            $category = DB::table('categories')->find($budget->category_id);
            if ($category) {
                DB::table('budgets')
                    ->where('id', $budget->id)
                    ->update(['category' => $category->name]);
            }
        }

        Schema::table('budgets', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
