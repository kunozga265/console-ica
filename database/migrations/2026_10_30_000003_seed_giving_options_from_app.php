<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The app now reads giving options from the web. Start the table with the
     * accounts the app used to hard-code, so the Give screen isn't empty —
     * only when nothing has been entered yet. Edit them under Admin → Giving Options.
     */
    public function up(): void
    {
        if (! Schema::hasTable('giving_options') || DB::table('giving_options')->exists()) {
            return;
        }

        $now = now();
        $rows = [
            ['bank', 'ICA Current Account', 'International Christian Assembly', '320347', 'National Bank'],
            ['mobile', 'TNM Mpamba', null, '602027', null],
            ['mobile', 'Airtel Money', null, 'ICABOX', null],
            ['bank', 'ICA Building', 'International Christian Assembly', '331627', 'National Bank'],
            ['bank', 'ICA Cell Group', 'International Christian Assembly', '1000388568', 'National Bank'],
        ];
        foreach ($rows as $i => [$type, $name, $accountName, $number, $branch]) {
            DB::table('giving_options')->insert([
                'type' => $type, 'name' => $name, 'account_name' => $accountName, 'account_number' => $number,
                'branch' => $branch, 'swift_code' => null, 'instructions' => null,
                'sort_order' => $i, 'active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Data seed; leave whatever admins have since edited.
    }
};
