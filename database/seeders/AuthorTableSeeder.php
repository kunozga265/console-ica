<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AuthorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Dr. Enson
        $author= new Author([
            "avatar"      => "images/enson_lwesya.jpg",
            "name"        =>  "Enson Lwesya",
            "suffix"      =>  "Rev. Dr. ",
            "title"       =>  "Senior Pastor",
            "slug"        => Str::slug("Enson Lwesya").date("-Y-m-d"),
            "ica_pastor"  => 1,
            "biography"   => "This is a biography"
        ]);
        $author->save();

        //Dr. Mkwaila
        $author= new Author([
            "avatar"      => "images/andrew_mkwaila.jpg",
            "name"        =>  "Andrew Mkwaila",
            "suffix"      =>  "Dr.",
            "title"       =>  "Executive Pastor",
            "slug"        => Str::slug("Andrew Mkwaila").date("-Y-m-d"),
            "ica_pastor"  => 1,
            "biography"   => "This is a biography"
        ]);
        $author->save();

        //Ps. Seira
        $author= new Author([
            "avatar"      => "images/avatar.png",
            "name"        =>  "Seira Mitha",
            "suffix"      =>  "Ps.",
            "title"       =>  "Children's Pastor",
            "slug"        => Str::slug("Seira Mitha").date("-Y-m-d"),
            "ica_pastor"  => 0,
            "biography"   => "This is a biography"
        ]);
        $author->save();

        //Ps. Jerry Zimba
        $author= new Author([
            "avatar"      => "images/jerry_zimba.jpg",
            "name"        =>  "Jerry Zimba",
            "suffix"      =>  "Ps. ",
            "title"       =>  "Youth Pastor",
            "slug"        => Str::slug("Jerry Zimba").date("-Y-m-d"),
            "ica_pastor"  => 0,
            "biography"   => "This is a biography"
        ]);
        $author->save();
    }
}
