<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TreeStoreRequest;
use TreeService;
use TreeRepository;
use DateTimeHelper;
use App\Http\Resources\TreeResource;
use Illuminate\Support\Facades\Log;

class TreeController extends Controller
{

    /**
     * @OA\Post(path="/tree/store",
     *   tags={"tree"},
     *   summary="Create or Update tree",
     *   description="This api used to create Or update the tree. If tree exist then tree will be updated otherwise new tree will be created.",
     *   operationId="createUpdateTree",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Create Update Tree",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *                 @OA\Property(
     *                     property="topic_num",
     *                     description="The topic number of topic",
     *                     required=true,
     *                     type="integer",
     *                     format="int32"
     *                 ),
     *                 @OA\Property(
     *                     property="asofdate",
     *                     description="Updated status of the pet",
     *                     required=true,
     *                     type="integer",
     *                     format="int32"
     *                 ),
     *                 @OA\Property(
     *                     property="algorithm",
     *                     description="current selected algorithm",
     *                     required=true,
     *                     type="string"
     *                 ),
     *                @OA\Property(
     *                     property="update_all",
     *                     description="if update_all is 0 then tree will be created using algortihm which sends in api otherwise tree will be created for all the algorithms",
     *                     required=false,
     *                     type="integer",
     *                     format="int32"
     *                 )
     *       )
     *   ),
     *
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="success",
     *                                         type="boolean"
     *                                    )
     *                                 )
     *                            )
     *
     *   @OA\Response(response=401, description="Exception occurs during tree calculation",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="success",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    )
     *                                 )
     *                             )
     *   @OA\Response(response=404,
     *                description="Tree not found",
     *                @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="success",
     *                                         type="boolean"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="string"
     *                                    )
     *                          )
     *                  )
     * )
     */

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

        $start = microtime(true);

        $tree =  TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request);

        $end = microtime(true);
        $time = $end - $start;

        Log::info("Time via store method: ". $time);

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
        $start = microtime(true);

        $asOfDate =  DateTimeHelper::getAsOfDate($asOfTime);
        $conditions =  TreeService::getConditions($topicNumber, $algorithm, $asOfDate);
        $tree =  TreeRepository::findTree($conditions);

        $end = microtime(true);
        $time = $end - $start;

        Log::info("Time via find method: ". $time);

        // create tree if not found
        if($tree->isEmpty() || !$tree){
           $tree =  array(TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
        }

        return new TreeResource($tree);
    }
}
