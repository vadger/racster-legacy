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
        Schema::table('racster_user_payments', function (Blueprint $table) {
            $table->timestamp('sub_period_end')->nullable()->index()->after('stripe_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('racster_user_payments', function (Blueprint $table) {
            $table->dropColumn('sub_period_end');
        });
    }
};
