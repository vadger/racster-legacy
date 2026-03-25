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
        Schema::create('racster_entries', function (Blueprint $table) {

            $table->id();
			$table->bigInteger('creator_id');

			$table->string('entry_title')->nullable();
			$table->mediumText('entry_description')->nullable();

			$table->bigInteger('entry_type')->nullable();
			$table->bigInteger('client_level')->nullable();
			$table->tinyInteger('various_clients')->default(0);
			$table->tinyInteger('recurring_entry')->default(0);
			$table->decimal('entry_monthly_fee', 20, 3)->nullable();

            $table->timestamps();
			$table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racster_entries');
    }
};
