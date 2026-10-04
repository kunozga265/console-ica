<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('series')) {
            Schema::create('series', function (Blueprint $table) {
                $table->id();
                $table->string("title");
                $table->string("slug");
                $table->text("description")->nullable();
                $table->integer('theme_id')->nullable();
                $table->integer('first_sermon_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
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
        Schema::dropIfExists('series');
    }
}
