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
        Schema::create('racster_user_transactions', function (Blueprint $table) {

            $table->id();
			$table->bigInteger('creator_id');

			$table->string('transaction_type')->nullable();
			$table->decimal('transaction_amount', 20, 3)->nullable();
			$table->mediumText('transaction_comment')->nullable();
			$table->bigInteger('date_id')->nullable();
			$table->bigInteger('user_id')->nullable();
			$table->string('stripe_sess_id')->nullable();

            $table->timestamps();
			$table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racster_user_transactions');
    }
};
