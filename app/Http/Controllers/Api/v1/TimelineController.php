<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimelineStoreRequest;
use App\Http\Resources\TimelineResource;
use DateTimeHelper;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use TimelineRepository;
use TreeService;
use TimelineService;
use UtilHelper;
use App\Model\v1\Topic;
use App\Model\v1\Camp;
use App\Model\v1\Statement;
use App\Services\CampService;
use App\Services\TopicService;
use Throwable;

class TimelineController extends Controller
{
    /**
     * @OA\Post(path="/timeline/store",
     *   tags={"timeline"},
     *   summary="Create or Update timeline",
     *   description="This api used to create Or update the timeline. If timeline exist then timeline will be updated otherwise new timeline will be created.",
     *   operationId="createUpdateTimeline",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Create Update Timeline",
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
     *                     description="if update_all is 0 then timeline will be created using algortihm which sends in api otherwise timeline will be created for all the algorithms",
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
     *   @OA\Response(response=401, description="Exception occurs during timeline calculation",
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
     *                description="Timeline not found",
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
     * Store a new timeline.
     *
     * @param  TimelineStoreRequest  $request
     * @return Response
     */

    public function store(TimelineStoreRequest $request)
    {
        try{
        Log::info(($request));
        /* get input params from request */
        $topicNumber = (int) $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOfTime = (int) $request->input('asofdate');
        $updateAll = (int) $request->input('update_all', 0);
        //new paramerter adding
        $message = $request->input('message');
        $type = $request->input('type');
        $id =   $request->input('id');
        $old_parent_id =  $request->input('old_parent_id');
        $new_parent_id =  $request->input('new_parent_id');
        //end

        $start = microtime(true);
        $currentTime = time();

        /**
         * Update each topic grace period where grace period duration is completed
         */
        $topics = Topic::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->get();
        
        if($topics->count() > 0) {
            foreach($topics as $topic) {
                $submittedTime = $topic->submit_time;
                $gracePeriodEndTime = $submittedTime + (60*60);
                if($currentTime > $gracePeriodEndTime) {
                    $topic->grace_period = 0;
                    $topic->update();
                }
            }
        }
        

        /**
         * Update each camp grace period where grace period duration is completed
         */
        $camps = Camp::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->get();
        
        if($camps->count() > 0) {
            foreach($camps as $camp) {
                $submittedTime = $camp->submit_time;
                $gracePeriodEndTime = $submittedTime + (60*60);
                if($currentTime > $gracePeriodEndTime) {
                    $camp->grace_period = 0;
                    $camp->update();
                }
            }
        }

        /**
         * Update each statement grace period where grace period duration is completed
         */
        $statements = Statement::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->get();
        
        if($statements->count() > 0) {
            foreach($statements as $statement) {
                $submittedTime = $statement->submit_time;
                $gracePeriodEndTime = $submittedTime + (60*60);
                if($currentTime > $gracePeriodEndTime) {
                    $statement->grace_period = 0;
                    $statement->update();
                }
            }
        }

        $timeline = TimelineService::upsertTimeline($topicNumber, $algorithm, $asOfTime, $updateAll, $request, $message, $type, $id, $old_parent_id, $new_parent_id);

        $end = microtime(true);
        $time = $end - $start;

        Log::info("Time via store method: " . $time);

        return new TimelineResource(array($timeline));
    } catch (Throwable $e) {
        $errResponse = UtilHelper::exceptionResponse($e, $request->input('tracing') ?? false);
        return response()->json($errResponse, 500);
    }
    }

    /**
     * @OA\Post(path="/timeline/get",
     *   tags={"timeline"},
     *   summary="fetch or create a timeline",
     *   description="This api used to get Or create the timeline. If timeline exist then timeline will be fetched otherwise new timeline will be created.",
     *   operationId="createUpdateTree",
     *   @OA\RequestBody(
     *       required=true,
     *       description="fetch and create a Timeline",
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
     *                     property="algorithm",
     *                     description="current selected algorithm",
     *                     required=true,
     *                     type="string"
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
     * @param  TimelineStoreRequest  $request
     * @return Response
     */

    public function find(TimelineStoreRequest $request)
    {
        try{
        /* get input params from request */
        $topicNumber = (int) $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        /** Get Cron Run date from .env file and make timestring */
        $cronDate = UtilHelper::getCronRunDateString();

        // get the tree from mongoDb
        $start = microtime(true);
        $conditions = TimelineService::getConditions($topicNumber, $algorithm);
        $mongoTree = TimelineRepository::findTimeline($conditions);
        // First check the topic exist in database, then we can run upsertTimeline.
        //$topicExistInMySql = TopicService::checkTopicInMySql($topicNumber);
        
       /* if ((!$mongoTree || !count($mongoTree)) && $topicExistInMySql) {
            $mongoTree = array(TimelineService::upsertTimeline($topicNumber, $algorithm, $asOfTime, $updateAll, $request, $message, $type, $id, $old_parent_id, $new_parent_id));
        }*/
       
        if($mongoTree && count($mongoTree)) {
            $tree = collect([$mongoTree[0]]);
        }
 
        $end = microtime(true);
        $time = $end - $start;

        $response = new TimelineResource($tree);
        $collectionToJson = json_encode($response, true);
        $responseArray = json_decode($collectionToJson, true);
        
        // Below code is for checking the requested camp number is created on the asOfTime.
        if(array_key_exists('data', $responseArray) && count($responseArray['data'])) {
            $response = $responseArray;
        }
        
        Log::info("Time via find method: " . $time);

        return $response;
        } catch (Throwable $e) {
            $errResponse = UtilHelper::exceptionResponse($e, $request->input('tracing') ?? false);
            return response()->json($errResponse, 500);
        }
    }
}
