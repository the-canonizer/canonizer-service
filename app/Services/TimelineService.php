<?php

namespace App\Services;

use CampService;
use TimelineRepository;
use DateTimeHelper;
use TopicService;
use AlgorithmService;
use App\Exceptions\Camp\CampTreeException;
use App\Exceptions\Camp\CampURLException;
use App\Exceptions\Camp\CampDetailsException;
use App\Exceptions\Camp\CampSupportCountException;
use App\Exceptions\Camp\CampTreeCountException;
use Illuminate\Support\Facades\Log;


class TimelineService
{

    /**
     * get mongo array to store in mongodb.
     *
     * @param  array tree
     * @param  Illuminate\Support\Collection $topic
     * @param  Request $request
     * @param int $asOfTime
     * @return array $mongoArr
     */

    public function prepareMongoArr($tree, $topic = null, $reviewTopic = null, $asOfDate = null, $algorithm = null, $topicCreatedByNickId = null, $message, $type, $id=null, $old_parent_id=null, $new_parent_id=null,$k=0)
    {

        $namespaceId = isset($topic->namespace_id) ? $topic->namespace_id : '';
        $reviewNamespaceId = isset($reviewTopic->namespace_id) ? $reviewTopic->namespace_id : '';
        $topicScore = isset($tree[1]['score']) && !is_string($tree[1]['score']) ? $tree[1]['score'] : 0;
        $topicFullScore = isset($tree[1]['full_score']) && !is_string($tree[1]['full_score']) ? $tree[1]['full_score'] : 0;
        $topicTitle = isset($tree[1]['title']) ? $tree[1]['title'] :  '';
        $topicNumber = isset($tree[1]['topic_id']) ? $tree[1]['topic_id'] :  '';
        $submitter_nick_id = isset($tree[1]['submitter_nick_id']) ? $tree[1]['submitter_nick_id'] :  '';
        $asOfDate =$asOfDate;
        $mongoArr = [
                "asoftime_".$asOfDate."_".$k => array(
                    "event" => array(
                        'message'=>$message,
                        'type'=> $type,
                        'id'=> $id,
                        'old_parent_id'=> $old_parent_id,
                        'new_parent_id'=> $new_parent_id,
                        'nickname_id'=>$topicCreatedByNickId
                    ),
                    "payload_response" => $this->array_single_dimensional($tree)
                ), 
        ];
        
        return $mongoArr;
    }

    /**
     * get upsert conditions to insert or create a tree.
     *
     * @param  int topicNumber
     * @param  string $algorithm
     * @param int $asOfTime
     *
     * @return array $conditions
     */

    public function getConditions($topicNumber, $algorithm)
    {
        return [
            'topic_id' => $topicNumber,
            'algorithm_id' => $algorithm
        ];
    }


    /**
     * create or update the tree
     *
     * @param int topicNumber
     * @param string $algorithm
     * @param int $asOfTime
     * @param int updateAll | default 0
     * @param Illuminate\Http\Request | defualt Empty array
     *
     * @return array $array
     */

    public function upsertTimeline($topicNumber, $algorithm, $asOfTime, $updateAll = 0, $request = [], $message, $type, $id, $old_parent_id, $new_parent_id, $timelineType="",$k=0)
    {
       
        $algorithms =  AlgorithmService::getCacheAlgorithms($updateAll, $algorithm);
        if($timelineType=="history"){
            $rootUrl =  $this->getRootUrlHistory($request);
        }
        else{
            $rootUrl =  $this->getRootUrl($request);
        }
        $startCamp = 1;
        $topicCreatedByNickId = TopicService::getTopicAuthor($topicNumber);
        foreach ($algorithms as $algo) {
            try {
                
                if($timelineType=="history"){
                    $tree = CampService::prepareCampTimeline($algo, $topicNumber, $asOfTime, $startCamp, $rootUrl,$nickNameId = null, $asOf = 'bydate', $fetchTopicHistory = 0);
                    $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false],$asOf = 'bydate', $fetchTopicHistory = 0);
                }
                else{
                    $tree = CampService::prepareCampTimeline($algo, $topicNumber, $asOfTime, $startCamp, $rootUrl);
                    $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);
                }
                $topicInReview = TopicService::getReviewTopic($topicNumber);
                //get date string from timestamp
                $asOfDate = $asOfTime;
                $mongoArr = $this->prepareMongoArr($tree, $topic, $topicInReview, $asOfDate, $algo, $topicCreatedByNickId, $message, $type, $id, $old_parent_id, $new_parent_id,$k);
                $conditions = $this->getConditions($topicNumber, $algo, $asOfDate);

            } catch (CampTreeException | CampDetailsException | CampTreeCountException | CampSupportCountException | CampURLException | \Exception $th) {
                return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
            }

            $tree = TimelineRepository::upsertTimeline($mongoArr, $conditions);
        }

        return $tree;
    }
    
    
    /**
     * Get Topic tree from mysql if it is not exist in mongodb
     *
     * @param int topicNumber
     * @param string $algorithm
     * @param int $asOfTime
     * @param int updateAll | default 0
     * @param Illuminate\Http\Request | defualt Empty array
     *
     * @return array $array
     */
    public function getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll = 0, $request = [], $fetchTopicHistory = 0){

        $rootUrl =  $this->getRootUrl($request);
        $asOf = $request->asOf ?? 'default';
        $startCamp = 1;
        try {
           $tree = CampService::prepareCampTree($algorithm, $topicNumber, $asOfTime, $startCamp, $rootUrl, $nickNameId = null, $asOf, $fetchTopicHistory);
        }
        catch (CampTreeException | CampDetailsException | CampTreeCountException | CampSupportCountException | CampURLException | \Exception $th) {
            return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
        }

        return $tree;
    }


    /**
     * Get root url
     *
     * @param Illuminate\Http\Request
     *
     * @return string $rootUrl
    */
    public function getRootUrl($request){

         $url = request()->headers->get('referer');
         $url = rtrim($url,"/");
         $rootUrl = isset($url) ? $url:env('REFERER_URL');

         return $rootUrl;
    }

    /**
     * get tree array convert to linear form.
     *
     * @param  array tree
     * @return array $singleDimensional
    */
    public function array_single_dimensional($tree)
    {
        $singleDimensional = [];
        foreach ($tree as $item) {
            $children =  isset($item['children']) ? $item['children'] : null; //temporarily store children if set
            unset($item['children']); //delete children before adding to new array
            $singleDimensional[] = $item; // add parent to new array
            if ( !empty($children) ){ // if has children
                //convert children to single dimensional
                $childrenSingleDimensional = $this->array_single_dimensional($children);
    
                //merge the two, this line did the trick!
                $singleDimensional = array_merge($singleDimensional, $childrenSingleDimensional); 
            }
        }
        return $singleDimensional;
    }

    /**
        * Get root url
        *
        * @param Illuminate\Http\Request
        *
        * @return string $rootUrl
    */
    public function getRootUrlHistory($request){
        $rootUrl = env('REFERER_URL');
        return $rootUrl;
    }

}
