<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What each user may manage (App\Support\Scopes). Super users implicitly
     * have every scope; everyone else gets them assigned individually.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_scopes')) {
            Schema::create('user_scopes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('scope', 32);
                $table->timestamps();
                $table->unique(['user_id', 'scope']);
            });

            // The old "admin" role meant "manages everything": carry that over.
            $scopes = ['sermons', 'prayer', 'events', 'announcements', 'giving', 'ministries', 'cells', 'registers', 'members'];
            $admins = DB::table('user_role')->join('roles', 'roles.id', '=', 'user_role.role_id')
                ->where('roles.name', 'admin')->pluck('user_role.user_id')->unique();
            foreach ($admins as $userId) {
                foreach ($scopes as $scope) {
                    DB::table('user_scopes')->insertOrIgnore(['user_id' => $userId, 'scope' => $scope, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_scopes');
    }
};
