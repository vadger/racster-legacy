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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_paused')->nullable()->after('stripe_status');
            $table->string('pause_behavior')->nullable()->after('is_paused'); // void | keep_as_draft | mark_uncollectible
            $table->timestamp('pause_resumes_at')->nullable()->after('pause_behavior');
            $table->timestamp('paused_at')->nullable()->after('pause_resumes_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_paused','pause_behavior','pause_resumes_at','paused_at']);
        });
    }
};
