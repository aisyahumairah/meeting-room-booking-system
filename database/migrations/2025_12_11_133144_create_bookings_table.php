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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique(); // BK-2025-00001

            // Relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('series_id')->nullable()->constrained('booking_series')->onDelete('cascade');

            // Booking details
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('purpose'); // max 500 chars (validated in app)

            // Status: confirmed, cancelled, completed (NO pending/rejected)
            $table->enum('status', ['confirmed', 'cancelled', 'completed'])->default('confirmed');

            // Cancellation details
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['booking_date', 'status']);
            $table->index(['user_id', 'booking_date']);
            $table->index(['room_id', 'booking_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
