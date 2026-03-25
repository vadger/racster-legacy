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
		Schema::create('notification_filter_location', function (Blueprint $table) {
			$table->id();
			$table->foreignId('notification_filter_id')
				  ->constrained('notification_filters')
				  ->cascadeOnDelete();

			$table->foreignId('location_id')->constrained('racster_assets')->cascadeOnDelete();

			$table->unique(['notification_filter_id', 'location_id'], 'nf_location_filter_unique');
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_filter_location');
    }
};
