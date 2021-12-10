<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TreeStoreRequest;
use CampService;
use TreeService;
use TreeRepository;

class TreeController extends Controller
{

     /**
     * Store a new tree.
     *
     * @param  TreeStoreRequest  $request
     * @return Response
     */

    public function store(TreeStoreRequest $request)
    {
        $topicNumber = $request->input('topic_num');
        $algorithm = $request->input('algorithm');

        $tree = CampService::prepareCampTree($algorithm, $topicNumber);
        $topic = CampService::getAgreementTopic($topicNumber, $request);
        $mongoArr = TreeService::prepareMongoArr($tree, $topic, $request);

        if (TreeRepository::createTree($mongoArr)) {
            return response()->json(["code" => 200, "success" => true]);
        }

        return response()->json(["code" => 400, "success" => false], 400);
    }
}
