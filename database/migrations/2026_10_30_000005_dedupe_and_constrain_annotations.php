<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One note per user per sermon, and one bookmark / highlight per user per
     * sentence (span id). Remove duplicates left by repeated app syncs, then
     * let the database enforce it.
     */
    public function up(): void
    {
        $this->dedupe('notes', ['user_id', 'sermon_id'], 'updated_at DESC, id DESC');
        // Prefer the newest bookmark that has a comment.
        $this->dedupe('bookmarks', ['user_id', 'sermon_id', 'caption_id'], "(comment IS NOT NULL AND comment <> '') DESC, updated_at DESC, id DESC");
        $this->dedupe('highlights', ['user_id', 'sermon_id', 'highlight_id'], 'id ASC');

        $this->unique('notes', ['user_id', 'sermon_id'], 'notes_user_sermon_unique');
        $this->unique('bookmarks', ['user_id', 'sermon_id', 'caption_id'], 'bookmarks_user_sermon_caption_unique');
        $this->unique('highlights', ['user_id', 'sermon_id', 'highlight_id'], 'highlights_user_sermon_span_unique');
    }

    /** Keep the first row per group in $order; delete the rest. */
    private function dedupe(string $table, array $columns, string $order): void
    {
        $groups = DB::table($table)->select($columns)->groupBy($columns)->havingRaw('count(*) > 1')->get();

        foreach ($groups as $group) {
            $match = fn ($q) => $q->where(array_map(fn ($c) => [$c, '=', $group->{$c}], $columns));
            $keep = DB::table($table)->where($match)->orderByRaw($order)->value('id');
            DB::table($table)->where($match)->where('id', '!=', $keep)->delete();
        }
    }

    private function unique(string $table, array $columns, string $name): void
    {
        if (! Schema::hasIndex($table, $name)) {
            Schema::table($table, fn (Blueprint $t) => $t->unique($columns, $name));
        }
    }

    public function down(): void
    {
        foreach (['notes' => 'notes_user_sermon_unique', 'bookmarks' => 'bookmarks_user_sermon_caption_unique', 'highlights' => 'highlights_user_sermon_span_unique'] as $table => $name) {
            if (Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropUnique($name));
            }
        }
    }
};
