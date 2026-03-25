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
        Schema::create('scheduled_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to_email');
            $table->string('subject');
            $table->longText('body');
			$table->string('heading')->nullable();
			$table->string('cta_name')->nullable();
			$table->string('cta_link')->nullable();
			$table->string('stripe_invid')->nullable();
            $table->timestamp('send_at')->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('status')->default('pending'); // pending|queued|sent|failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_emails');
    }
};
