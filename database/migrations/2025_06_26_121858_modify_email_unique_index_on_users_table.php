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
            // Drop the single‐column unique index:
            $table->dropUnique('users_email_unique');
            $table->dropUnique('users_google_id_unique');

            // Add a composite unique on email + deleted_at | google_id + deleted_at
            $table->unique(['email', 'deleted_at'], 'users_email_deleted_at_unique');
			$table->unique(['google_id', 'deleted_at'], 'users_google_id_deleted_at_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the composite unique:
            $table->dropUnique('users_email_deleted_at_unique');
			$table->dropUnique('users_google_id_deleted_at_unique');

            // Re-create the original unique on email | google_id:
            $table->unique('email');
			$table->unique('google_id');
        });
    }
};
