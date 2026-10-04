<?php

namespace Database\Seeders;

use App\Models\Ministry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MinistryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Ministry::create([
            "name" => "Main Church",
            "slug" => Str::slug("Main Church"),
        ]);

        Ministry::create([
            "name" => "Men's Ministry",
            "slug" => Str::slug("Men's Ministry"),
        ]);

        Ministry::create([
            "name" => "Women's Ministry",
            "slug" => Str::slug("Women's Ministry"),
        ]);

        Ministry::create([
            "name" => "Children's Ministry",
            "slug" => Str::slug("Children's Ministry"),
        ]);

        Ministry::create([
            "name" => "Contemporary Church",
            "slug" => Str::slug("Youth and Young Adults Ministry"),
        ]);

        Ministry::create([
            "name" => "Teens Ministry",
            "slug" => Str::slug("Teens Ministry"),
        ]);

        Ministry::create([
            "name" => "Sunrise Ministries",
            "slug" => Str::slug("Sunrise Ministries"),
        ]);
    }
}
