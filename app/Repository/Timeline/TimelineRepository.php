<?php

namespace App\Repository\Timeline;

use App\Model\v1\Timeline;
use App\Repository\Timeline\TimelineInterface;

class TimelineRepository implements TimelineInterface
{

    protected $model;
    /**
     * Instantiate a new TimelineRepository instance.
     *
     * @return void
     */
    public function __construct(Timeline $timeline)
    {
        $this->model = $timeline;
    }

    /**
     * create a new timeline.
     *
     * @param  array timeline
     * @return boolean Response
     */

    public function createTimeline($timeline)
    {

        try {
            $record = Timeline::create($timeline);
            return $record->wasRecentlyCreated;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * create or update a timeline.
     *
     * @param  array timeline
     * @param  array $conditions | assocative array
     *
     * @return boolean Response
     */

    public function upsertTimeline($timelineArr, $conditions)
    {
        try {
            $record = Timeline::updateOrCreate(
                $conditions,
                $timelineArr
            );
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * find a timeline.
     *
     * @param  array $conditions | assocative array
     *
     * @return array Response
     */

    public function findTimeline($conditions)
    {
        try {

            $record = Timeline::where($conditions)->get();
            return $record;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
