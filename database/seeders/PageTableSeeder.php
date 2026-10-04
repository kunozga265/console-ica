<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Page::create([
            "name" => "announcements",
            "contents" => json_encode([
                "body" => "",
                "activate" => 0,
            ])
        ]);
        Page::create([
            "name" => "fundraising",
            "contents" => json_encode([
                "target" => 0,
                "collected" => 0,
                "heading" => "",
                "title" => "",
                "description" => "",
                "image" => "",
                "activate" => 0,
            ])
        ]);
    }
}
