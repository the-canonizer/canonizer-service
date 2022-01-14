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

    public function prepareMongoArr($tree, $topic = null, $request = null, $asOfDate = null, $algorithm = null)
    {

        $namespaceId = isset($topic->namespace_id) ? $topic->namespace_id : '';
        $topicScore = isset($tree[1]['score']) ? $tree[1]['score'] : 0;

        $mongoArr = [
            "topic_id" => $request->input('topic_num'),
            "algorithm_id" => $algorithm,
            "tree_structure" => $tree,
            "namespace_id" => $namespaceId,
            "topic_score" =>  $topicScore,
            "as_of_date" => $asOfDate
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

        // $rootUrl = env('REFERER_URL');
        $rootUrl = request()->headers->get('referer');
        $startCamp = 1;

        foreach ($algorithms as $algo) {
            try {

                $tree = CampService::prepareCampTree($algo, $topicNumber, $asOfTime, $startCamp, $rootUrl);
                $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);

                //get date string from timestamp
                $asOfDate = DateTimeHelper::getAsOfDate($request);
                $mongoArr = $this->prepareMongoArr($tree, $topic, $request, $asOfDate, $algo);
                $conditions = $this->getConditions($topicNumber, $algo, $asOfDate);
            } catch (CampTreeException | CampDetailsException | CampTreeCountException | CampSupportCountException | CampURLException | \Exception $th) {
                return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
            }

            $tree = TreeRepository::upsertTree($mongoArr, $conditions);
        }

        return $tree;
    }

}
