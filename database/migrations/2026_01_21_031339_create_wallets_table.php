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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Main BCA", "Cash"
            $table->enum('type', ['cash', 'bank', 'ewallet'])->default('cash');
            $table->decimal('balance', 15, 2)->default(0);
            $table->enum('currency', ['IDR', 'USD'])->default('IDR');
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable(); // e.g., "BCA", "GoPay"
            $table->string('color')->default('bg-indigo-500');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
