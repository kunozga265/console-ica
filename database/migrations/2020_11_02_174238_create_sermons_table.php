<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSermonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('sermons')) {
            Schema::create('sermons', function (Blueprint $table) {
                $table->id();
                $table->string("title");
                $table->string("slug");
                $table->string("subtitle")->nullable();
                $table->string("video_url")->nullable();
                $table->text("body");
                $table->integer("author_id");
                $table->integer("series_id")->nullable();
                $table->integer("category_id")->nullable();
                $table->double("published_at");
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
        Schema::dropIfExists('sermons');
    }
}
