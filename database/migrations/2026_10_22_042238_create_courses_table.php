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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('key_stage', 16);
            $table->string('subject_area', 64);
            $table->string('summary', 500);
            $table->text('description')->nullable();
            $table->unsignedInteger('price_per_hour')->comment('In pence');
            $table->unsignedInteger('price_monthly')->comment('In pence');
            $table->unsignedSmallInteger('hours_per_month')->default(4);
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'key_stage']);
        });

        Schema::create('course_teacher', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['course_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_teacher');
        Schema::dropIfExists('courses');
    }
};
