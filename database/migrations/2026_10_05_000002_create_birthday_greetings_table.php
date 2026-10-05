<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** One row per member per year, so a re-run of the 7am job never emails twice. */
    public function up(): void
    {
        if (! Schema::hasTable('birthday_greetings')) {
            Schema::create('birthday_greetings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id');
                $table->unsignedSmallInteger('year');
                $table->string('email');
                $table->timestamp('sent_at');
                $table->unique(['member_id', 'year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_greetings');
    }
};
