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
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('stream_url', 2048)->nullable();
            $table->string('recording_url', 2048)->nullable();
            $table->string('status', 16)->default('scheduled');
            $table->timestamp('seven_day_reminder_sent_at')->nullable();
            $table->timestamp('two_day_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['course_id', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
