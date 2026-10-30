<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pastoral follow-ups with visitors (members with is_registered = false). */
    public function up(): void
    {
        if (! Schema::hasTable('visitor_follow_ups')) {
            Schema::create('visitor_follow_ups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('user_id')->nullable(); // who logged it
                $table->date('date');
                $table->text('comment');
                $table->date('next_date')->nullable()->index(); // next check-up
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_follow_ups');
    }
};
