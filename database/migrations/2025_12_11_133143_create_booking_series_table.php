<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_series', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 25)->unique(); // BK-SERIES-2025-00001

            // Relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');

            // Recurrence settings
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly']);
            $table->json('recurrence_pattern'); // e.g., {"interval": 1, "days_of_week": [1, 3, 5]}
            $table->date('start_date');
            $table->date('end_date');

            // Common booking details
            $table->time('start_time');
            $table->time('end_time');
            $table->text('purpose');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_series');
    }
};
