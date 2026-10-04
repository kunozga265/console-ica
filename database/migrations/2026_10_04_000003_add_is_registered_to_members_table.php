<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visitors are people recorded before they're fully registered. They are
     * members rows with is_registered = false; registering them flips it.
     * Everyone already in the table is a full member.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('members', 'is_registered')) {
            Schema::table('members', function (Blueprint $table) {
                $table->boolean('is_registered')->default(true)->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('members', 'is_registered')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropIndex(['is_registered']);
                $table->dropColumn('is_registered');
            });
        }
    }
};
