<?php

namespace App\Services;

use App\Model\v1\Topic;

class TopicService
{

    /**
     * get live topic details.
     *
     * @param  int $topicNumber
     * @param int $asOfTime
     * @param  array $filter
     * @return Illuminate\Support\Collection
     */

    public function getLiveTopic($topicNumber, $asOfTime, $filter = array())
    {

        if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "default")) {

            return Topic::where('topic_num', $topicNumber)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', $asOfTime)
                ->latest('submit_time')->first();
        } else {

            if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "review")) {

                return Topic::where('topic_num', $topicNumber)
                    ->where('objector_nick_id', '=', NULL)
                    ->latest('submit_time')->first();
            } else if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate")) {

                if (isset($filter['nofilter']) && $filter['nofilter']) {
                    $asOfTime  = time();
                }

                return Topic::where('topic_num', $topicNumber)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', $asOfTime)
                    ->latest('submit_time')->first();
            }
        }
    }
}
