<?php

namespace App\Listeners;

use App\Events\IncreaseTopicViewCountEvent;
use App\Helpers\Helpers;
use App\Model\v1\TopicView;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncreaseTopicViewCountListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  IncreaseTopicViewCountEvent  $event
     * @return void
     */
    public function handle(IncreaseTopicViewCountEvent $event)
    {
        if (Hash::driver('argon2id')->check($event->asOfTime, '$argon2id$v=19$m=' . env('HASH_MEMORY_COST') . ',t=' . env('HASH_ITERATION') . ',p=' . env('HASH_PARALLELISM_FACTOR') . $event->view)) {
            if ($view = Helpers::getCampViewByDay($event->topic_num, $event->camp_num, Carbon::now())) {
                $view->increment('views');
            } else {
                TopicView::create(['topic_num' => $event->topic_num, 'camp_num' => $event->camp_num, 'views' => 1]);
            }
        }
    }

    /**
     * Get the name of the listener's queue.
     */
    public function viaQueue(): string
    {
        return 'camp-view-count';
    }
}
