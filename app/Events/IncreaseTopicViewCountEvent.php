<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncreaseTopicViewCountEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $topic_num;

    public $camp_num;

    public $asOfTime;

    public $view;

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
