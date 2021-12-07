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
     * @return array $mongoArr
     */

    public function prepareMongoArr($tree, $topic = null, $request = null)
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
            "as_of_date" => strtotime($request->input('asofdate'))
        ];

        return $mongoArr;
    }
}
