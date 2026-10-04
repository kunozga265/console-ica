<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sermons and events now belong to a ministry. Existing rows default to
     * Main Church (falling back to the first ministry if it was renamed).
     */
    public function up(): void
    {
        foreach (['sermons', 'events'] as $table) {
            if (!Schema::hasColumn($table, 'ministry_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('ministry_id')->nullable()->index();
                });
            }
        }

        $mainChurch = DB::table('ministries')->where('slug', 'main-church')->value('id')
            ?? DB::table('ministries')->orderBy('id')->value('id');

        if ($mainChurch) {
            DB::table('sermons')->whereNull('ministry_id')->update(['ministry_id' => $mainChurch]);
            DB::table('events')->whereNull('ministry_id')->update(['ministry_id' => $mainChurch]);
        }
    }

    public function down(): void
    {
        foreach (['sermons', 'events'] as $table) {
            if (Schema::hasColumn($table, 'ministry_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropIndex(['ministry_id']);
                    $table->dropColumn('ministry_id');
                });
            }
        }
    }
};
