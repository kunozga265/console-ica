<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Children and teenagers (18 or under) may have a guardian contact instead of their own. */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'guardian_name')) {
                $table->string('guardian_name')->nullable();
            }
            if (! Schema::hasColumn('members', 'guardian_phone')) {
                $table->string('guardian_phone', 32)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            foreach (['guardian_name', 'guardian_phone'] as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
