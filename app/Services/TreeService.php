<?php

namespace App\Services;

use CampService;
use TreeRepository;
use DateTimeHelper;
use TopicService;
use AlgorithmService;
use App\Exceptions\Camp\CampTreeException;
use App\Exceptions\Camp\CampURLException;
use App\Exceptions\Camp\CampDetailsException;
use App\Exceptions\Camp\CampSupportCountException;
use App\Exceptions\Camp\CampTreeCountException;



class TreeService
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

    public function prepareMongoArr($tree, $topic = null, $reviewTopic = null, $asOfDate = null, $algorithm = null, $topicCreatedByNickId = null)
    {

        $namespaceId = isset($topic->namespace_id) ? $topic->namespace_id : '';
        $reviewNamespaceId = isset($reviewTopic->namespace_id) ? $reviewTopic->namespace_id : '';
        $topicScore = isset($tree[1]['score']) && !is_string($tree[1]['score']) ? $tree[1]['score'] : 0;
        $topicFullScore = isset($tree[1]['full_score']) && !is_string($tree[1]['full_score']) ? $tree[1]['full_score'] : 0;
        $topicTitle = isset($tree[1]['title']) ? $tree[1]['title'] :  '';
        $topicNumber = isset($tree[1]['topic_id']) ? $tree[1]['topic_id'] :  '';
        $submitter_nick_id = isset($tree[1]['submitter_nick_id']) ? $tree[1]['submitter_nick_id'] :  '';

        $mongoArr = [
        "topic_id" => $topicNumber,
        "topic_name" => $topicTitle,
        "algorithm_id" => $algorithm,
        "tree_structure" => $tree,
        "namespace_id" => $namespaceId,
        "review_namespace_id" => $reviewNamespaceId,
        "topic_score" => $topicScore,
        "topic_full_score" => $topicFullScore,
        "as_of_date" => $asOfDate,
        "submitter_nick_id" =>$submitter_nick_id,
        "created_by_nick_id"=>$topicCreatedByNickId
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

    public function getConditions($topicNumber, $algorithm, $asOfDate)
    {
        return [
            'topic_id' => $topicNumber,
            'algorithm_id' => $algorithm,
            'as_of_date' => $asOfDate
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

    public function upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll = 0, $request = [])
    {

        $algorithms =  AlgorithmService::getCacheAlgorithms($updateAll, $algorithm);
        $rootUrl =  $this->getRootUrl($request);
        $startCamp = 1;
        $topicCreatedByNickId = TopicService::getTopicAuthor($topicNumber);
        
        foreach ($algorithms as $algo) {
            try {

                $tree = CampService::prepareCampTree($algo, $topicNumber, $asOfTime, $startCamp, $rootUrl);
                echo "<pre>"; print_r($tree); die;
                $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);
                $topicInReview = TopicService::getReviewTopic($topicNumber);
                //get date string from timestamp
                $asOfDate = DateTimeHelper::getAsOfDate($asOfTime);
                $mongoArr = $this->prepareMongoArr($tree, $topic, $topicInReview, $asOfDate, $algo, $topicCreatedByNickId);
                $conditions = $this->getConditions($topicNumber, $algo, $asOfDate);

            } catch (CampTreeException | CampDetailsException | CampTreeCountException | CampSupportCountException | CampURLException | \Exception $th) {
                return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
            }

            $tree = TreeRepository::upsertTree($mongoArr, $conditions);
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

}
