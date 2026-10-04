<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->id();
                $table->string("code");
                $table->string("avatar")->nullable();
                $table->string("first_name");
                $table->string("middle_name")->nullable();
                $table->string("last_name");
                $table->string("gender");
                $table->double("date_of_birth")->nullable();
                $table->integer("cell_id")->nullable();
                $table->string("phone_number_airtel")->nullable();
                $table->string("phone_number_tnm")->nullable();
                $table->string("phone_number_international")->nullable();
                $table->string("email")->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('members');
    }
}
