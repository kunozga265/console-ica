<?php

namespace App\Models;

use App\Http\Controllers\Web\AppController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Sermon extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    public function author()
    {
        return $this->belongsTo("App\Models\Author");
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

    public function views(){
        return $this->belongsTo("App\Models\View","sermon_id");
    }

    public function searchableAs(){
      return "sermons_index";
    }

    public function refactorBody()
    {
        return (new AppController())->generateHighlightLinks($this->body);
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
    ];
}
