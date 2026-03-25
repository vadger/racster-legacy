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
        Schema::create('stripe_product_prices', function (Blueprint $table) {

			$table->id();

			$table->foreignId('product_id')->constrained('stripe_products')->cascadeOnDelete();
			$table->unsignedBigInteger('unit_amount'); // minor units
			$table->string('currency', 10)->default('eur');
			$table->string('interval')->nullable(); // null for one-off
			$table->boolean('active')->default(true);
			$table->boolean('is_custom')->default(true);
			$table->string('stripe_price_id')->nullable()->unique();
			$table->string('hash')->index(); // (product_id, amount, currency, interval)

			$table->timestamps();
			$table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_product_prices');
    }
};
