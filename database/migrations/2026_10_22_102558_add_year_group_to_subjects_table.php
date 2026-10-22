<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A null year group means the subject is for every year in the course's key stage.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedTinyInteger('year_group')->nullable()->after('name');
            $table->index(['course_id', 'year_group']);
        });

        DB::table('subjects')->orderBy('id')->each(function (object $subject): void {
            if (preg_match('/^Year\s+(7|8|9|10|11)$/i', trim($subject->name), $matches)) {
                DB::table('subjects')->where('id', $subject->id)->update(['year_group' => (int) $matches[1]]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'year_group']);
            $table->dropColumn('year_group');
        });
    }
};
