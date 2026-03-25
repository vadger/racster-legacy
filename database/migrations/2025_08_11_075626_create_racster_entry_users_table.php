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
        Schema::create('racster_entry_users', function (Blueprint $table) {

            $table->id();
			$table->bigInteger('creator_id');
			$table->bigInteger('entry_id');
			$table->bigInteger('date_id')->nullable();

			$table->string('user_type')->nullable();
			$table->bigInteger('user_id');
			$table->integer('user_quantity')->nullable()->default(1);
			$table->tinyInteger('paying')->default(0);

            $table->timestamps();
			$table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racster_entry_users');
    }
};
