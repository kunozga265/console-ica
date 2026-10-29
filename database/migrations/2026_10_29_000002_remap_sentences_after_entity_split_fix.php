<?php

use App\Http\Controllers\Web\HelperController;
use App\Models\Sermon;
use App\Support\SentenceRemap;
use App\Support\SermonBody;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The sentence splitter no longer breaks at the ";" of an HTML entity
     * ("&rdquo; He said", "Death &amp; Resurrection"), which merges sentences and
     * renumbers them. Move saved highlights/bookmarks to the new numbers and
     * touch the sermons so the mobile app re-downloads the corrected bodies.
     */
    public function up(): void
    {
        Sermon::withTrashed()->select(['id', 'body'])->chunkById(50, function ($sermons) {
            foreach ($sermons as $sermon) {
                $legacy = new HelperController();
                $legacy->legacyEntitySplit = true;
                $old = $legacy->generateHighlightLinks(SermonBody::normalizeLists($sermon->body));
                $new = $sermon->refactorBody();
                if ($old === $new) {
                    continue;
                }
                $map = SentenceRemap::map($old, $new);

                DB::transaction(function () use ($sermon, $map) {
                    if ($map) {
                        $this->remap('highlights', 'highlight_id', $sermon->id, $map);
                        $this->remap('bookmarks', 'caption_id', $sermon->id, $map);
                    }
                    DB::table('sermons')->where('id', $sermon->id)->update(['updated_at' => now()]);
                });
            }
        });
    }

    private function remap(string $table, string $column, int $sermonId, array $map): void
    {
        $seen = [];
        $rows = DB::table($table)->where('sermon_id', $sermonId)->orderBy('id')->get(['id', 'user_id', $column]);

        foreach ($rows as $row) {
            $target = $map[(int) $row->{$column}] ?? (int) $row->{$column};
            $key = $row->user_id . ':' . $target;
            if ($table === 'highlights' && isset($seen[$key])) {
                DB::table($table)->where('id', $row->id)->delete(); // two halves of one sentence, now one line
                continue;
            }
            $seen[$key] = true;
            if ($target !== (int) $row->{$column}) {
                DB::table($table)->where('id', $row->id)->update([$column => $target]);
            }
        }
    }

    public function down(): void
    {
        // Renumbering is not reversible; nothing to undo.
    }
};
