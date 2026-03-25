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
        Schema::create('racster_assets', function (Blueprint $table) {
            $table->id();
			$table->integer('uid');						// Define the creator ID
			$table->string('type', 20);					// Asset type (file, link, ...)
			$table->bigInteger('parent')->nullable();	// ID of the parent element
			$table->string('title');					// Name of the asset
			$table->mediumText('descr')->nullable();	// Description for the asset
			$table->mediumText('extra')->nullable();	// Extra info for the asset
            $table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racster_assets');
    }
};
