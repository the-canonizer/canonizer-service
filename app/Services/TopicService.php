<?php

namespace App\Services;

use App\Model\v1\Topic;
use Illuminate\Support\Facades\Log;

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

        // if (isset($filter['nofilter']) && $filter['nofilter']) {
        //     $asOfTime  = time();
        // }

        $sql = Topic::where('topic_num', $topicNumber)
        ->where('objector_nick_id', '=', NULL)
        ->where('go_live_time', '<=', $asOfTime)
        ->latest('submit_time')->toSql();
        Log::info("#######################");
        Log::info("Topic Query ".$sql);
        Log::info("#######################");

        return Topic::where('topic_num', $topicNumber)
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asOfTime)
            ->latest('submit_time')->first();
    }


    /**
     * get review topic details.
     *
     * @param  int $topicNumber
     * @return Illuminate\Support\Collection
     */
    public function getReviewTopic($topicNumber)
    {
        return Topic::where('topic_num', $topicNumber)
            ->where('objector_nick_id', '=', NULL)
            ->where('grace_period', '=', 0)
            ->latest('submit_time')->first();
    }
}
