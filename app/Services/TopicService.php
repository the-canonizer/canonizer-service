<?php

namespace App\Services;

use App\Model\v1\Topic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TopicService
{

    /**
     * get live topic details.
     *
     * @param  int $topicNumber
     * @param  int $asOfTime
     * @param  array $filter
     * @return Illuminate\Support\Collection
     */

    public function getLiveTopic($topicNumber, $asOfTime, $filter = array())
    {

        DB::reconnect('mysql');
        // return Topic::where('topic_num', $topicNumber)
        //     ->where('objector_nick_id', '=', NULL)
        //     ->where('go_live_time', '<=', $asOfTime)
        //     ->latest('submit_time')->first();
       return DB::select('select * from topic where topic_num = ? and objector_nick_id is null and go_live_time <= ? order by submit_time desc limit 1', [$topicNumber, $asOfTime])[0];
    }


    /**
     * get review topic details.
     *
     * @param  int $topicNumber
     * @return Illuminate\Support\Collection
     */
    public function getReviewTopic($topicNumber)
    {
        DB::reconnect('mysql');
        return Topic::where('topic_num', $topicNumber)
            ->where('objector_nick_id', '=', NULL)
            ->where('grace_period', '=', 0)
            ->latest('submit_time')->first();
    }
}
