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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Event info
            $table->string('event_type', 50); // login, logout, booking_created, room_edited, etc.

            // Actor (who performed the action)
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 100); // Stored separately in case user is deleted

            // Target (what was affected)
            $table->string('target_type', 50)->nullable(); // user, booking, room, etc.
            $table->unsignedBigInteger('target_id')->nullable();

            // Details
            $table->json('details')->nullable(); // Old/new values, extra context

            // Request info
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Timestamp only (immutable - no updated_at)
            $table->timestamp('created_at')->useCurrent();

            // Indexes for filtering
            $table->index('event_type');
            $table->index('actor_id');
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
