<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone_number_airtel')) {
                $table->string("phone_number_airtel")->nullable();
            }
            if (!Schema::hasColumn('users', 'phone_number_tnm')) {
                $table->string("phone_number_tnm")->nullable();
            }
            if (!Schema::hasColumn('users', 'phone_number_international')) {
                $table->string("phone_number_international")->nullable();
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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone_number_airtel')) {
                $table->dropColumn('phone_number_airtel');
            }
            if (Schema::hasColumn('users', 'phone_number_tnm')) {
                $table->dropColumn('phone_number_tnm');
            }
            if (Schema::hasColumn('users', 'phone_number_international')) {
                $table->dropColumn('phone_number_international');
            }
        });
    }
}
