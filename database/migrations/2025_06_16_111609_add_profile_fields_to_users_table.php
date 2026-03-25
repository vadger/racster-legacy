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
        Schema::table('users', function (Blueprint $table) {
			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();
			$table->string('mobile_country_code')->nullable();
			$table->string('mobile_number')->nullable();
			$table->date('birthday')->nullable();
			$table->string('profile_image')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('first_name');
			$table->dropColumn('last_name');
			$table->dropColumn('mobile_country_code');
			$table->dropColumn('mobile_number');
			$table->dropColumn('birthday');
			$table->dropColumn('profile_image');
		});
    }
};
