<?php

namespace App\Models;

use App\Http\Controllers\Web\HelperController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Sermon extends Model
{
    use HasFactory;
    // use Searchable;
    use SoftDeletes;

    public function author()
    {
        return $this->belongsTo("App\Models\Author");
    }

    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }

    public function authorName()
    {
        return $this->author->suffix. " " . $this->author->name;
    }
    public function series()
    {
        return $this->belongsTo("App\Models\Series");
    }

    public function category()
    {
        return $this->belongsTo("App\Models\Category");
    }

    /** View counters (views.sermon_id → sermons.id); one row per user/device. */
    public function viewRecords()
    {
        return $this->hasMany(View::class);
    }

    public function views(){
        return $this->belongsTo("App\Models\View","sermon_id");
    }

    public function highlights()
    {
        return $this->hasMany(Highlight::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function searchableAs(){
      return "sermons_index";
    }

    /**
     * When fill-in-the-blank answers become visible (unix seconds): 24 hours
     * after publishing, or earlier if an admin revealed them.
     */
    public function fillsRevealAt(): int
    {
        $auto = intval($this->published_at) + 86400;

        return $this->fills_revealed_at ? min($auto, intval($this->fills_revealed_at)) : $auto;
    }

    /** Ids of the body's blanks (<mark data-fill="…">), in order. */
    public function fillKeys(): array
    {
        preg_match_all('/<mark\b[^>]*\bdata-fill=["\']([a-z0-9]{4,16})["\']/i', (string) $this->body, $m);

        return array_values(array_unique($m[1]));
    }

    public function refactorBody()
    {
        return (new HelperController())->generateHighlightLinks($this->body);
    }

    protected $fillable=[
        "title",
        "slug",
        "subtitle",
        "body",
        "author_id",
        "series_id",
        "video_url",
        "category_id",
        "published_at",
        "ministry_id",
    ];
}
