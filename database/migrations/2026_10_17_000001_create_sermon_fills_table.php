<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fill-in-the-blank answers members type into a sermon's blanks
     * (<mark data-fill="key"> in the body), one row per user per blank.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sermon_fills')) {
            Schema::create('sermon_fills', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('sermon_id')->index();
                $table->string('fill_key', 16);
                $table->string('value');
                $table->timestamps();
                $table->unique(['user_id', 'sermon_id', 'fill_key']);
            });
        }

        // Set by admins to reveal a sermon's answers before the automatic 24 hours.
        if (! Schema::hasColumn('sermons', 'fills_revealed_at')) {
            Schema::table('sermons', function (Blueprint $table) {
                $table->unsignedBigInteger('fills_revealed_at')->nullable()->after('published_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sermon_fills');

        if (Schema::hasColumn('sermons', 'fills_revealed_at')) {
            Schema::table('sermons', fn (Blueprint $table) => $table->dropColumn('fills_revealed_at'));
        }
    }
};
