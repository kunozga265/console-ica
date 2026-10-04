<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Factories\MemberFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        //        Member::factory(50)->create();

        $this->call([
            UserTableSeeder::class,
                       AuthorTableSeeder::class,
                       SeriesTableSeeder::class,
                       SermonTableSeeder::class,
                       ThemeTableSeeder::class,
            CategoryTableSeeder::class,
                       PageTableSeeder::class,
            MinistryTableSeeder::class,
            ZoneTableSeeder::class,
            //            UserTableSeeder::class,
                       PrayersTableSeeder::class,
            RoleTableSeeder::class,
               CellsRefactorySeeder::class,

        ]);

        // $users = User::all();
        // foreach ($users as $user){
        //     $user->update(["id" => $user->id]);
        //     $user->member?->update(['avatar'=>$user->avatar]);
        // }

    }
}
