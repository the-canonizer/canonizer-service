<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TreeStoreRequest;
use CampService;
use TreeService;
use TreeRepository;
use DateTimeHelper;

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
        /* get input params from request */
        $topicNumber = $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOf = $request->input('asof');
        $asOfTime =  DateTimeHelper::getAsOfTime($request);

        $tree = CampService::prepareCampTree($algorithm, $topicNumber, $asOfTime);
        $topic = CampService::getAgreementTopic($topicNumber, $request, $asOfTime);
        $mongoArr = TreeService::prepareMongoArr($tree, $topic, $request, $asOfTime);
        $conditions =  TreeService::getConditions($topicNumber, $algorithm, $asOf, $asOfTime);

        if (TreeRepository::upsertTree($mongoArr, $conditions)) {
            return response()->json(["code" => 200, "success" => true]);
        }

        return response()->json(["code" => 400, "success" => false], 400);
    }


    /**
     * Store a new tree.
     *
     * @param  TreeStoreRequest  $request
     * @return Response
     */

    public function find(TreeStoreRequest $request)
    {
        /* get input params from request */
        $topicNumber = $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOf = $request->input('asof');
        $asOfTime =  DateTimeHelper::getAsOfTime($request);

        $conditions =  TreeService::getConditions($topicNumber, $algorithm, $asOf, $asOfTime);
        $tree =  TreeRepository::findTree($conditions);

        if (count($tree) > 0) {
            return response()->json(["data" => $tree, "code" => 200, "success" => true]);
        }

        return response()->json(["data" => [], "code" => 404, "success" => false], 404);
    }
}
