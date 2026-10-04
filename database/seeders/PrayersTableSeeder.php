<?php

namespace Database\Seeders;

use App\Models\Prayer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PrayersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $prayers = Prayer::all();
        foreach ($prayers as $prayer){
            $prayer->update([
                'slug' => Str::slug($prayer->title)
            ]);
        }
    }
}
