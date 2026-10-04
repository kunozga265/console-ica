<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Zone::create([
            "name" => "East Zone",
            "leaders" => "Mr and Mrs Mtema"
        ]);

        Zone::create([
            "name" => "West/South West",
            "leaders" => "Mr and Mrs Chalamanda"
        ]);

        Zone::create([
            "name" => "South Zone",
            "leaders" => "Ps and Mrs Zimba"
        ]);

        Zone::create([
            "name" => "North Zone",
            "leaders" => "Ms Manguluti",
        ]);

        Zone::create([
            "name" => "North West",
            "leaders" => "Ps and Mrs Phiri/Mrs Machinjika"
        ]);

        Zone::create([
            "name" => "ICA Central",
            "leaders" => "Mr and Mrs Chilemba"
        ]);

        Zone::create([
            "name" => "Central Zone",
            "leaders" => "Ps Seira Mitha"
        ]);

        Zone::create([
            "name" => "North East",
            "leaders" => "Mr and Mrs Banda"
        ]);

        Zone::create([
            "name" => "South East",
            "leaders" => "Ps and Mrs Mlowoka"
        ]);

        Zone::create([
            "name" => "Youth",
            "leaders" => "Mr and Mrs Mkali"
        ]);

        Zone::create([
            "name" => "Asian Community",
            "leaders" => "Ps Samuel Jibaraj"
        ]);

        Zone::create([
            "name" => "Kiswahili",
            "leaders" => ""
        ]);
    }
}
