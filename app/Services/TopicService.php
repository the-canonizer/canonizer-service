<?php

namespace App\Services;

use App\Model\v1\Topic;
use TopicRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Model\v1\Camp;
use CampService;
use DateTimeHelper;

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

    public function getLiveTopic($topicNumber, $asOfTime, $filter = array(), $asOf = 'default', $fetchTopicHistory = 0)
    {
        $topic =  Topic::where('topic_num', $topicNumber);
                        if($asOf == 'default' || $asOf == 'review') {
                            $topic->where('objector_nick_id', NULL);
                        }
                       
                        if($asOf == 'default') {
                            $topic->where('go_live_time', '<=', time());
                        }
                        if($asOf == 'bydate') {
                            $topic->where('go_live_time', '<=', $asOfTime);
                        }
                        
        $liveTopic = $topic->orderBy('go_live_time', 'desc')->first(); // ticket 1219 Muhammad Ahmad
        
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
        $topic = Topic::where('topic_num', $topicNumber)
            ->where('grace_period', 0)
            ->where('objector_nick_id', NULL)
            ->orderBy('go_live_time', 'desc')->first(); // ticket 1219 Muhammad Ahmad

          return $topic;
    }

    /**
     * get topics with score from mongoDb.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param int $skip
     * @param int $pageSize
     * @param float $filter
     * @param string $search
     *
     *
     * @return array Response
     */

    public function getTopicsWithScore($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asof = 'default'){

        /** if filter param set then only get those topics which have score more than give filter */
        $topicsWithScore = (isset($filter) && $filter!=null && $filter!='') ?
                 TopicRepository::getTopicsWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asof):
                 TopicRepository::getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $nickNameIds, $search, $asof);

        return $topicsWithScore;
    }

    /**
     * get total topics with given conditions from MongoDb.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param float $filter
     * @param string $search
     *
     *
     * @return int $totalTrees
     */

    public function getTotalTopics($namespaceId, $asofdate, $algorithm, $filter, $nickNameIds, $search, $asof = 'default'){

        /** if filter param set then only get those topics which have score more than give filter */
        $totalTopics = (isset($filter) && $filter!=null && $filter!='') ?
                      TopicRepository::getTotalTopicsWithFilter($namespaceId, $asofdate, $algorithm, $filter, $nickNameIds, $search, $asof):
                      TopicRepository::getTotalTopics($namespaceId, $asofdate, $algorithm, $nickNameIds, $search, $asof);

        $totalTopics = count($totalTopics) ?? 0;

        return $totalTopics;
    }


    /**
     * Sort the topics based on score.
     *
     * @param int $namespaceId
     * @param string $algorithm
     * @param int $asOfTime
     *
     * @return Illuminate\Database\Eloquent\Collection;
     */
    public  function sortTopicsBasedOnScore($topics, $algorithm, $asOfTime){

        if(sizeof($topics) > 0){

                 foreach ($topics as $key => $value) {
                    $campData = Camp::where('topic_num',$value->topic_num)->where('camp_num',$value->camp_num)->first();
                    if( $campData){
                        $reducedTree = CampService::prepareCampTree($algorithm, $value->topic_num, $asOfTime, $value->camp_num);
                        $topics[$key]->score = !is_string($reducedTree[$value->camp_num]['score']) ? $reducedTree[$value->camp_num]['score'] : 0;
                        $topics[$key]->topic_score = !is_string($reducedTree[$value->camp_num]['score']) ? $reducedTree[$value->camp_num]['score'] : 0;
                        $topics[$key]->topic_id = $reducedTree[$value->camp_num]['topic_id'];
                        $topics[$key]->topic_name = $reducedTree[$value->camp_num]['title'];
                        $topics[$key]->tree_structure[1]['review_title'] = $reducedTree[$value->camp_num]['review_title'];
                        $topics[$key]->as_of_date = DateTimeHelper::getAsOfDate($value->go_live_time);                        
                    }else{
                        $topics[$key]->score = 0;
                        $topics[$key]->topic_score = 0;
                        $topics[$key]->topic_id = $value->topic_num;
                        $topics[$key]->topic_name = $value->title;
                        $topics[$key]->tree_structure[1]['review_title'] = $value->title;
                        $topics[$key]->as_of_date = DateTimeHelper::getAsOfDate($value->go_live_time);
                    }
                    
                }
              // $topics = $topics->sortBy('score',SORT_REGULAR, true);
                $topics = collect(collect($topics)->sortByDesc('score'))->values();
                return $topics;
        }else{
            return $topics;
        }
    }

    /**
     * Filter the topics collection .
     *
     * @param Illuminate\Database\Eloquent\Collection
     *
     * @return Illuminate\Database\Eloquent\Collection;
     */

     public function filterTopicCollection($topics, $filter){

        $filteredTopics = $topics->filter(function ($value, $key) use($filter) {
            return $value->score > $filter;
        });

        return $filteredTopics;

     }

    /**
     * Get the topic created date .
     *
     * @param Illuminate\Database\Eloquent\Collection
     *
     * @return Illuminate\Database\Eloquent\Collection;
     */

    public function getTopicCreatedDate($topicNumber){
        return Topic::where('topic_num', $topicNumber)
                ->pluck('submit_time')
                ->first();
    }

    /**
     * Check topic exists in MySql .
     *
     * @param Illuminate\Database\Eloquent\Collection
     *
     * @return Illuminate\Database\Eloquent\Collection;
     */

    public function checkTopicInMySql($topicNumber) {
        return Topic::where('topic_num', $topicNumber)->first();
    }
}
