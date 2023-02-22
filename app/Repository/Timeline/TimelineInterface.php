<?php

namespace App\Repository\Timeline;

interface TimelineInterface
{
    public function createTimeline($timelineArr);
    public function upsertTimeline($timelineArr, $conditions);
    public function findTimeline($conditions);
}
