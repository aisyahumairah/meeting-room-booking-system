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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->integer('capacity')->unsigned();
            $table->string('floor_location', 100);
            $table->text('description')->nullable(); // max 500 chars enforced in validation
            $table->enum('status', ['active', 'inactive', 'under_maintenance'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['status']);
            $table->index(['capacity']);
            $table->index(['floor_location']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
