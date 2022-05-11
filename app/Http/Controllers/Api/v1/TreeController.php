<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TreeStoreRequest;
use App\Http\Resources\TreeResource;
use DateTimeHelper;
use Illuminate\Support\Facades\Log;
use TreeRepository;
use TreeService;
use UtilHelper;

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
        $topicNumber = (int) $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOfTime = (int) $request->input('asofdate');
        $updateAll = (int) $request->input('update_all', 0);

        $start = microtime(true);

        $tree = TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request);

        $end = microtime(true);
        $time = $end - $start;

        Log::info("Time via store method: " . $time);

        return new TreeResource(array($tree));
    }

    /**
     * @OA\Post(path="/tree/get",
     *   tags={"tree"},
     *   summary="fetch or create a tree",
     *   description="This api used to get Or create the tree. If tree exist then tree will be fetched otherwise new tree will be created.",
     *   operationId="createUpdateTree",
     *   @OA\RequestBody(
     *       required=true,
     *       description="fetch and create a Tree",
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
     * get a tree.
     *
     * @param  TreeStoreRequest  $request
     * @return Response
     */

    public function find(TreeStoreRequest $request)
    {
        /* get input params from request */
        $topicNumber = (int) $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOfTime = (int) $request->input('asofdate');
        $updateAll = (int) $request->input('update_all', 0);
        $fetchTopicHistory =  $request->input('fetch_topic_history');
        $asOfDate = DateTimeHelper::getAsOfDate($asOfTime);
        
        /** Get Cron Run date from .env file and make timestring */
        $cronDate = UtilHelper::getCronRunDateString();

        // get the tree from mongoDb
        $start = microtime(true);

        /* if $asofdate is greater then cron run date then get tree
         * with score from mongodb instance else fetch tree with Score
         * from Mysql
         */
        
        if (($asOfDate >= $cronDate) && ($algorithm == 'blind_popularity' || $algorithm == "mind_experts") && !($fetchTopicHistory)) {
            
            $conditions = TreeService::getConditions($topicNumber, $algorithm, $asOfDate);

            /**
             * Fetch topic tree on the basis of jobs in queue or processed ones
             * If there is any job exists in jobs table, then topic tree isn't updated in Mongo yet -- Get tree from MySQL Database
             * If there is not job exists in jobs table, then there is no pending jobs -- need to check latest processed job status
             * If latest processed job status is failed, then topic tree isn't updated in Mongo yet -- Get tree from MySQL Database
             * If latest processed job status is success, and tree found, then topic tree has been updated in Mongo -- Get tree from Mongo Database
             * If latest processed job status is success, and tree not found, then topic tree hasn't been updated in Mongo -- Get tree from MySQL Database
             */

            $isLastJobPending = \DB::table('jobs')->where('model_id', $topicNumber)->first();
            $latestProcessedJobStatus  = \DB::table('processed_jobs')->where('topic_num', $topicNumber)->orderBy('id', 'desc')->first();
            
            if($isLastJobPending) {
                $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
            } else {
                if($latestProcessedJobStatus && $latestProcessedJobStatus->status == 'Success') {
                    $mongoTree = TreeRepository::findTree($conditions);

                    if (!$mongoTree || !count($mongoTree)) {
                        $mongoTree = array(TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                    }
                    if($mongoTree && count($mongoTree)) {
                        $tree = collect([$mongoTree[0]['tree_structure']]);
                    } else {
                        $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                    }
                } else {
                    $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                }
            }
        } else {
            //TODO: shift latest mind_expert algorithm from canonizer 2.0 from getSupportCountFunction
            $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
        }

        $end = microtime(true);
        $time = $end - $start;

        Log::info("Time via find method: " . $time);

        return new TreeResource($tree);
    }
}
