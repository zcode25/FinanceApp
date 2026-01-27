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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        // Seed existing categories as system if they match standard names
        $systemCategories = [
            'Saving',
            'Investment',
            'Food & Drink',
            'Transport',
            'Utilities',
            'Rent',
            'Shopping',
            'Entertainment',
            'Groceries'
        ];

        DB::table('categories')
            ->whereIn('name', $systemCategories)
            ->update(['is_system' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
