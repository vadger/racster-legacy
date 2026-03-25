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
        Schema::create('racster_user_payments', function (Blueprint $table) {

			$table->id();

			// Who paid
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();

			// Add local variables
			$table->foreignId('product_id')->nullable()->constrained('stripe_products')->nullOnDelete();
			$table->foreignId('product_price_id')->nullable()->constrained('stripe_product_prices')->nullOnDelete();
			$table->unsignedBigInteger('entry_id')->nullable()->index();
			$table->unsignedBigInteger('date_id')->nullable()->index();

			// Stripe identifiers
			$table->string('stripe_invoice_id')->nullable()->unique();
			$table->string('stripe_subscription_id')->nullable()->index();
			$table->string('stripe_payment_intent_id')->nullable()->index();
			$table->string('stripe_checkout_session_id')->nullable()->index();

			// Money
			$table->bigInteger('amount')->nullable(); // minor units (cents)
			$table->string('currency', 10)->nullable();

			// Period (subscriptions only)
			$table->timestamp('period_start')->nullable();
			$table->timestamp('period_end')->nullable();
			$table->string('covered_month', 7)->nullable()->index();

			// Status / reason
			$table->string('status', 32)->default('open');
			$table->string('billing_reason', 64)->nullable();

			// Free-form details
			$table->json('metadata')->nullable();

			$table->timestamps();
			$table->softDeletes();

			// Composite index
			$table->index(['user_id','covered_month']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racster_user_payments');
    }
};
