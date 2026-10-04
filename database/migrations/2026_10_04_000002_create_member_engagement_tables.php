<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user engagement for the /ui member pages:
     *  - event_responses: attending / not attending an event (for the admin dashboard)
     *  - prayer_user:     "I'm praying" for a prayer point
     *  - sermon_saves:    whole-sermon "saved" (bookmark) and "favorite" lists
     *  - cell_join_requests: a member asking a cell's owner/leaders to add them
     *  - ui_notifications: in-app alerts (e.g. a join request for your cell)
     */
    public function up(): void
    {
        if (!Schema::hasTable('event_responses')) {
            Schema::create('event_responses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id');
                $table->unsignedBigInteger('user_id');
                $table->boolean('attending');
                $table->timestamps();
                $table->unique(['event_id', 'user_id']);
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('prayer_user')) {
            Schema::create('prayer_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prayer_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->unique(['prayer_id', 'user_id']);
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('sermon_saves')) {
            Schema::create('sermon_saves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sermon_id');
                $table->unsignedBigInteger('user_id');
                $table->string('kind', 16); // 'saved' | 'favorite'
                $table->timestamps();
                $table->unique(['sermon_id', 'user_id', 'kind']);
                $table->index(['user_id', 'kind']);
            });
        }

        if (!Schema::hasTable('cell_join_requests')) {
            Schema::create('cell_join_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cell_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('member_id')->nullable();
                $table->text('message')->nullable();
                $table->string('status', 16)->default('pending'); // pending | approved | declined
                $table->unsignedBigInteger('handled_by')->nullable();
                $table->timestamps();
                $table->index(['cell_id', 'status']);
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('ui_notifications')) {
            Schema::create('ui_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('url')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_notifications');
        Schema::dropIfExists('cell_join_requests');
        Schema::dropIfExists('sermon_saves');
        Schema::dropIfExists('prayer_user');
        Schema::dropIfExists('event_responses');
    }
};
