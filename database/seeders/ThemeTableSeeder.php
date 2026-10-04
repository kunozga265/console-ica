<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ThemeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $theme  = new Theme([
            "title"         =>  "I'm Responsible",
            "slug"          =>  Str::slug("I'm Responsible"),
            "year"          =>  2021,
            "description"   =>  "This is the description of theme"
        ]);
        $theme->save();

        $theme  = new Theme([
            "title"         =>  "In His Presence",
            "slug"          =>  Str::slug("In His Presence"),
            "year"          =>  2020,
            "description"   =>  "This is the description of theme"
        ]);
        $theme->save();
    }
}
