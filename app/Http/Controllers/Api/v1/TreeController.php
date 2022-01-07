<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TreeStoreRequest;
use TreeService;
use TreeRepository;
use DateTimeHelper;
use App\Http\Resources\TreeResource;

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
        $asOfTime = $request->input('asofdate');
        $updateAll = $request->input('update_all', 0);

        $tree =  TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request);

        return new TreeResource(array($tree));
    }


    /**
     * get a tree.
     *
     * @param  TreeStoreRequest  $request
     * @return Response
     */

    public function find(TreeStoreRequest $request)
    {
        /* get input params from request */
        $topicNumber = $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOfTime = $request->input('asofdate');
        $updateAll = $request->input('update_all', 0);

        // get the tree from mongoDb
        $asOfDate =  DateTimeHelper::getAsOfDate($request);
        $conditions =  TreeService::getConditions($topicNumber, $algorithm, $asOfDate);
        $tree =  TreeRepository::findTree($conditions);

        // create tree if not found
        if($tree->isEmpty() || !$tree){
           $tree =  array(TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
        }

        return new TreeResource($tree);
    }
}
