<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('cells')) {
            Schema::create('cells', function (Blueprint $table) {
                $table->id();
                $table->string("code");
                $table->string("name");
                $table->string("details")->nullable();
                $table->string("location")->nullable();
                $table->integer("zone_id");
                $table->integer("type");
                $table->integer("user_id");
                $table->double("balance");
                $table->boolean("verified");
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
        Schema::dropIfExists('cells');
    }
}
