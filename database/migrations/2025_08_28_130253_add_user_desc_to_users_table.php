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
			// Add "user_desc" column to users table after column "user_roles"
            $table->mediumText('user_desc')->nullable()->after('user_roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
			// Remove "user_desc" column from users table
            $table->dropColumn('user_desc');
        });
    }
};
