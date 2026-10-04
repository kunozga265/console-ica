<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CellsRefactorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cells = \App\Models\Cell::all();
        foreach($cells as $cell){
            //move leaders
            $cell->user?->member?->update([
                'leader_cell_id' => $cell->id
            ]);
        }
    }
}
