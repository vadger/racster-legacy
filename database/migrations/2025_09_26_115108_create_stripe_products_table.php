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
        Schema::create('stripe_products', function (Blueprint $table) {

			$table->id();

			$table->string('name');
			$table->text('description')->nullable();
			$table->string('currency', 10)->default('eur');
			$table->unsignedBigInteger('default_amount')->nullable(); // minor units
			$table->string('interval')->nullable(); // 'month', 'year' or null for one-off
			$table->boolean('active')->default(true);
			$table->string('stripe_product_id')->nullable()->index();
			$table->json('metadata')->nullable();

			$table->timestamps();
			$table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_products');
    }
};
