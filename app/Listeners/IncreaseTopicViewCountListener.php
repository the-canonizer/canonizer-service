<?php

namespace App\Listeners;

use App\Events\IncreaseTopicViewCountEvent;
use App\Model\v1\TopicView;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncreaseTopicViewCountListener
{
    /**
     * Handle the event.
     *
     * @param  IncreaseTopicViewCountEvent  $event
     * @return void
     */
    public function handle(IncreaseTopicViewCountEvent $event)
    {
        if (Hash::driver(env('HASH_DRIVER'))->check($event->asOfTime, '$argon2id$v=19$m=1500,t=20,p=10' . $event->view)) {
            if ($view = TopicView::where(['topic_num' => $event->topic_num, 'camp_num' => $event->camp_num])->whereBetween('created_at', [Carbon::now()->startOfDay()->timestamp, Carbon::now()->endOfDay()->timestamp])->first()) {
                $view->increment('views');
            } else {
                TopicView::create(['topic_num' => $event->topic_num, 'camp_num' => $event->camp_num]);
            }
        }
    }
}
