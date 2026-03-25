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
			// Add "user_level" column to users table after column "user_desc"
            $table->string('user_level')->nullable()->after('user_desc');
			// Add "discount_amount" column to users table after column "user_level"
			$table->integer('discount_amount')->nullable()->default(0)->after('user_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
			// Remove "user_level" column from users table
            $table->dropColumn('user_level');
			// Remove "discount_amount" column from users table
            $table->dropColumn('discount_amount');
        });
    }
};
