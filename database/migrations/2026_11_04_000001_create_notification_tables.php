<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Personalised notifications (v1.4.2):
     *  - notification_preferences: per-user opt-outs by category (no row = on)
     *  - ministry_unsubscriptions: sermon ministries a user muted (no row = subscribed)
     *  - notified_at on sermons / prayers / events: sent once, when they go live
     *  - ui_notifications.type / ref: lets the app open the right screen from its inbox
     *  - birthday_wishes: "so-and-so wishes you a happy birthday", once a year per sender
     */
    public function up(): void
    {
        if (! Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('category', 32);
                $table->boolean('enabled');
                $table->timestamps();
                $table->unique(['user_id', 'category']);
            });
        }

        if (! Schema::hasTable('ministry_unsubscriptions')) {
            Schema::create('ministry_unsubscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('ministry_id');
                $table->timestamps();
                $table->unique(['user_id', 'ministry_id']);
            });
        }

        foreach (['sermons', 'prayers', 'events'] as $name) {
            if (Schema::hasTable($name) && ! Schema::hasColumn($name, 'notified_at')) {
                Schema::table($name, fn (Blueprint $table) => $table->timestamp('notified_at')->nullable());
            }
        }

        if (Schema::hasTable('ui_notifications')) {
            Schema::table('ui_notifications', function (Blueprint $table) {
                if (! Schema::hasColumn('ui_notifications', 'type')) {
                    $table->string('type', 32)->nullable();
                }
                if (! Schema::hasColumn('ui_notifications', 'ref')) {
                    $table->string('ref', 191)->nullable(); // slug or id the type points at
                }
            });
        }

        if (! Schema::hasTable('birthday_wishes')) {
            Schema::create('birthday_wishes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('from_user_id');
                $table->unsignedBigInteger('member_id');
                $table->unsignedSmallInteger('year');
                $table->timestamps();
                $table->unique(['from_user_id', 'member_id', 'year']);
                $table->index('member_id');
            });
        }

        // Everything already live counts as notified, so deploying doesn't push
        // the back catalogue. Scheduled sermons and future prayer points still
        // notify when their day comes.
        $tomorrow = \Carbon\Carbon::tomorrow(config('app.timezone'))->getTimestamp();
        $live = [
            'sermons' => fn ($q) => $q->where('published_at', '<=', now()->getTimestamp()),
            'prayers' => fn ($q) => $q->where('date', '<', $tomorrow),
            'events'  => fn ($q) => $q,
        ];
        foreach ($live as $name => $scope) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'notified_at')) {
                $scope(\DB::table($name)->whereNull('notified_at'))->update(['notified_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_wishes');
        Schema::dropIfExists('ministry_unsubscriptions');
        Schema::dropIfExists('notification_preferences');
        foreach (['sermons', 'prayers', 'events'] as $name) {
            if (Schema::hasColumn($name, 'notified_at')) {
                Schema::table($name, fn (Blueprint $table) => $table->dropColumn('notified_at'));
            }
        }
        if (Schema::hasColumn('ui_notifications', 'type')) {
            Schema::table('ui_notifications', fn (Blueprint $table) => $table->dropColumn(['type', 'ref']));
        }
    }
};
