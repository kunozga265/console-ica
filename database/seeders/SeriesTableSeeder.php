<?php

namespace Database\Seeders;

use App\Models\Series;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SeriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $series = new Series([
            "title"         =>  "Let us rebuild",
            "description"   =>  "This is Let us rebuild series description",
            "slug"          =>  Str::slug("Let us rebuild").date("-Y-m-d"),
            "theme_id"      =>  1,
            "first_sermon_date"      =>  1592697600,
        ]);
        $series->save();


        $series = new Series([
            "title"         =>  "A Life Sought After by Many",
            "description"   =>  "This is A Life Sought After by Many description",
            "slug"          =>  Str::slug("A Life Sought After by Many").date("-Y-m-d"),
            "theme_id"      =>  2,
            "first_sermon_date"      =>  1595721600,
        ]);
        $series->save();


        $series = new Series([
            "title"         =>  "Righteousness for Sinful People Like Me",
            "description"   =>  "This is Righteousness for Sinful People Like Me description",
            "slug"          =>  Str::slug( "Righteousness for Sinful People Like Me").date("-Y-m-d"),
            "theme_id"      =>  null,
            "first_sermon_date"      =>  1596931200,
        ]);
        $series->save();


        $series = new Series([
            "title"         =>  "Missional Partnerships",
            "description"   =>  "This is Missional Partnerships description",
            "slug"          =>  Str::slug( "Missional Partnerships").date("-Y-m-d"),
            "theme_id"      =>  null,
            "first_sermon_date"      =>  1604188800,
        ]);
        $series->save();

    }
}
