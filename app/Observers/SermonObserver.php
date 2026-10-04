<?php

namespace App\Observers;

use App\Models\Series;
use App\Models\Sermon;
use App\Models\View;

class SermonObserver
{
    public function created(Sermon $sermon): void
    {
        View::create([
            "sermon_id" => $sermon->id,
            "count" => 0,
        ]);

        $this->syncSeriesFirstSermonDate($sermon->series_id);
    }

    public function updated(Sermon $sermon): void
    {
        if ($sermon->wasChanged(["series_id", "published_at"])) {
            $this->syncSeriesFirstSermonDate($sermon->series_id);

            $originalSeriesId = $sermon->getOriginal("series_id");
            if ($originalSeriesId && $originalSeriesId != $sermon->series_id) {
                $this->syncSeriesFirstSermonDate($originalSeriesId);
            }
        }
    }

    public function deleted(Sermon $sermon): void
    {
        $this->syncSeriesFirstSermonDate($sermon->series_id);
    }

    protected function syncSeriesFirstSermonDate(?int $seriesId): void
    {
        if (!$seriesId) {
            return;
        }

        $series = Series::find($seriesId);
        if (!$series) {
            return;
        }

        $earliest = $series->sermons()->orderBy("published_at", "asc")->value("published_at");
        $series->update(["first_sermon_date" => $earliest]);
    }
}
