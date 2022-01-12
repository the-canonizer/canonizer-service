<?php

namespace App\Services;

use App\Model\v1\Topic;

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

        $liveTopic =  Topic::where('topic_num', $topicNumber)
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asOfTime);

        if($this->checkIfAnyReviewChangeExist($topicNumber, $asOfTime) > 0){
             $liveTopic =  $liveTopic->where('grace_period', '=', 1);
        }

        $liveTopic = $liveTopic->latest('submit_time')->first();

        return $liveTopic;
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


     /**
     * check that if all the grace periods are zeros or all changes go live
     *
     * @param  int $topicNumber
     * @param  int $asOfTime
     *
     * @return int count
     */

    public function checkIfAnyReviewChangeExist($topicNumber, $asOfTime){

        return Topic::where('topic_num', $topicNumber)
            ->where('objector_nick_id', '=', NULL)
            ->where('go_live_time', '<=', $asOfTime)
            ->where('grace_period', '=', 1)
            ->count();
    }
}
