<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $category=new Category([
           "name"       =>  "Impact Conference",
           "slug"       =>  Str::slug("Impact Conference 2020")
        ]);
        $category->save();

        $category=new Category([
           "name"       =>  "Easter Conference",
           "slug"       =>  Str::slug("Easter Conference 2021")
        ]);
        $category->save();
    }
}
