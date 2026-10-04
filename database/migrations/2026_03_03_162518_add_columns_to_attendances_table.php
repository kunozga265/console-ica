<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'register_id')) {
                $table->integer("register_id")->nullable();
            }
            if (!Schema::hasColumn('attendances', 'date')) {
                $table->double("date")->nullable();
            }
            if (!Schema::hasColumn('attendances', 'meta')) {
                $table->json("meta")->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'register_id')) {
                $table->dropColumn('register_id');
            }
            if (Schema::hasColumn('attendances', 'date')) {
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('attendances', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
}
