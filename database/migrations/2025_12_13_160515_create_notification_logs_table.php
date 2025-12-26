<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50); // welcome, password_reset, booking_confirmed, etc.
            $table->string('channel', 20)->default('email'); // email, sms (future)
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('content')->nullable(); // Store rendered content or summary
            $table->string('status', 20)->default('sent'); // sent, failed, pending
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Related IDs, additional context
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
