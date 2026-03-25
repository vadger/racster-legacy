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
        Schema::create('racster_entry_dates', function (Blueprint $table) {

            $table->id();
			$table->bigInteger('creator_id');
			$table->bigInteger('entry_id');

			$table->timestamp('entry_start')->nullable();
			$table->timestamp('entry_ending')->nullable();
			$table->bigInteger('entry_length')->nullable();
			$table->bigInteger('entry_location')->nullable();
			$table->tinyInteger('private_entry')->default(0);
			$table->integer('client_limit')->nullable();
			$table->integer('client_count')->nullable();

			$table->decimal('entry_quantity', 20, 3)->default(1);
			$table->string('entry_unit')->nullable();
			$table->decimal('entry_price', 20, 3)->nullable();
			$table->tinyInteger('extra_price')->default(0);
			$table->decimal('entry_total', 20, 3)->nullable();
			$table->integer('entry_discount_rate')->nullable()->default(0);
			$table->integer('entry_vat_percent')->nullable();
			$table->decimal('entry_total_discount_vat', 20, 3)->nullable();

            $table->timestamps();
			$table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racster_entry_dates');
    }
};
