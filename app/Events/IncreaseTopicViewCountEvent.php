<?php

namespace App\Events;

class IncreaseTopicViewCountEvent extends Event
{

    public $topic_num, $camp_num, $asOfTime, $view;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $topic_num, int $camp_num, int $asOfTime, string $view)
    {
        $this->topic_num = $topic_num;
        $this->camp_num = $camp_num;
        $this->asOfTime = $asOfTime;
        $this->view = $view;
    }
}
