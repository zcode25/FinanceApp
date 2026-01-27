<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use Carbon\Carbon;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add category_id column (nullable first)
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained('categories');
        });

        // 2. Data Migration
        $transactions = DB::table('transactions')->get();

        foreach ($transactions as $transaction) {
            $categoryName = $transaction->category;
            $userId = $transaction->user_id;

            // normalize name for loose matching if needed, but 'like' is better?
            // Actually, let's try exact match locally first, then loose match.
            // But we have Global Categories (user_id = null) or User Categories.

            $category = DB::table('categories')
                ->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->orWhereNull('user_id');
                })
                ->where('name', $categoryName)
                ->where('type', $transaction->type) // Ensure type matches too (Income vs Expense)
                ->first();

            if (!$category) {
                // Try Case Insensitive Match
                $category = DB::table('categories')
                    ->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)
                            ->orWhereNull('user_id');
                    })
                    ->where('name', 'LIKE', $categoryName)
                    ->where('type', $transaction->type)
                    ->first();
            }

            if ($category) {
                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update(['category_id' => $category->id]);
            } else {
                // Determine color
                $colors = [
                    'income' => 'bg-emerald-500',
                    'expense' => 'bg-gray-500' // Default
                ];

                // If "Saving" came in as "saving", we probably want to create "Saving" (capitalized)?
                // Let's just create it as is for now to presume data fidelity, 
                // BUT if it was "saving" (lowercase), maybe capitalize it?
                $nameToCreate = ucfirst($categoryName);

                // Create new User Category
                $newId = DB::table('categories')->insertGetId([
                    'user_id' => $userId,
                    'name' => $nameToCreate,
                    'type' => $transaction->type,
                    'color' => $colors[$transaction->type] ?? 'bg-gray-500',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update(['category_id' => $newId]);
            }
        }

        // 3. Make nullable -> not null (if we are sure), and drop old column
        // We will keep column for a hot second or just drop it? Plan said drop it.
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('category')->nullable();
        });

        // Restore date
        $transactions = DB::table('transactions')->get();
        foreach ($transactions as $t) {
            $cat = DB::table('categories')->find($t->category_id);
            if ($cat) {
                DB::table('transactions')->where('id', $t->id)->update(['category' => $cat->name]);
            }
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
