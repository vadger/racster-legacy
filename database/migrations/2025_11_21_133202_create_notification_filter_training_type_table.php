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
		Schema::create('notification_filter_training_type', function (Blueprint $table) {
			$table->id();
			$table->foreignId('notification_filter_id')
				  ->constrained('notification_filters')
				  ->cascadeOnDelete();

			$table->foreignId('training_type_id')->constrained('racster_assets')->cascadeOnDelete();

			$table->unique(['notification_filter_id', 'training_type_id'], 'nf_type_filter_unique');
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_filter_training_type');
    }
};
