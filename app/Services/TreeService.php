<?php

namespace App\Services;



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

    public function prepareMongoArr($tree, $topic = null, $request = null, $asOfTime = null)
    {

        $namespaceId = isset($topic->namespace_id) ? $topic->namespace_id : '';
        $topicScore = isset($tree[1]['score']) ? $tree[1]['score'] : 0;

        $mongoArr = [
            "topic_id" => $request->input('topic_num'),
            "algorithm_id" => $request->input('algorithm'),
            "tree_structure" => $tree,
            "namespace_id" => $namespaceId,
            "topic_score" =>  $topicScore,
            "as_of" => $request->input('asof'),
            "as_of_date" => $asOfTime
        ];

        return $mongoArr;
    }

    /**
     * get upsert conditions to insert or create a tree.
     *
     * @param  int topicNumber
     * @param  string $algorithm
     * @param  string $asOf
     * @param int $asOfTime
     *
     * @return array $conditions
     */

    public function getConditions($topicNumber, $algorithm, $asOf, $asOfTime)
    {

        if ($asOf == 'review') {

            return [
                'topic_id' => $topicNumber,
                'algorithm_id' => $algorithm,
                'as_of' => $asOf
            ];
        }

        return [
            'topic_id' => $topicNumber,
            'algorithm_id' => $algorithm,
            'as_of' => $asOf,
            'as_of_date' => $asOfTime
        ];
    }
}
