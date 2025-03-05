<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimelineStoreRequest;
use App\Http\Resources\TimelineResource;
use Illuminate\Support\Facades\Log;
use TimelineRepository;
use TimelineService;
use UtilHelper;
use App\Model\v1\Topic;
use App\Model\v1\Camp;
use App\Model\v1\Statement;
use App\Services\TopicService;
use Throwable;
use Illuminate\Support\Facades\Artisan;

class TimelineController extends Controller
{
    /**
     * @OA\Post(
     *   path="/v1/timeline/store",
     *   tags={"V1"},
     *   summary="Store a new timeline in the MongoDB database",
     *   description="This API stores a new timeline in the MongoDB database.",
     *   operationId="TimelineStoreV1",
     * 
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="algorithm", type="string"),
     *       @OA\Property(property="topic_num", type="integer"),
     *       @OA\Property(property="update_all", type="integer"),
     *     )
     *   ),
     * 
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="code", type="integer", example=200),
     *       @OA\Property(property="success", type="string"),
     *       @OA\Property(property="data", type="object")
     *     )
     *   ),
     * 
     *   @OA\Response(
     *     response=400,
     *     description="Exception occurs",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="code", type="integer", example=400),
     *       @OA\Property(property="error", type="string", example="message"),
     *       @OA\Property(property="data", type="object", nullable=true)
     *     )
     *   )
     * )
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
            $url =  $request->input('url');
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
            Log::info("check url: " . $url);

            $timeline = TimelineService::upsertTimeline($topicNumber, $algorithm='', $asOfTime, $updateAll, $request, $message, $type, $id, $old_parent_id, $new_parent_id, $timelineType="", $topic_name="", $camp_num=null, $camp_name="", $k=0, $url);

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
     * @OA\Post(
     *   path="/v1/timeline/get",
     *   tags={"V1"},
     *   summary="Get a timeline from the MongoDB database",
     *   description="This API gets a timeline of specific topic from the MongoDB database.",
     *   operationId="TimelineGetV1",
     * 
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="algorithm", type="string"),
     *       @OA\Property(property="topic_num", type="integer"),
     *       @OA\Property(property="update_all", type="integer"),
     *     )
     *   ),
     * 
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="code", type="integer", example=200),
     *       @OA\Property(property="success", type="string"),
     *       @OA\Property(property="data", type="object")
     *     )
     *   ),
     * 
     *   @OA\Response(
     *     response=400,
     *     description="Exception occurs",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="code", type="integer", example=400),
     *       @OA\Property(property="error", type="string", example="message"),
     *       @OA\Property(property="data", type="object", nullable=true)
     *     )
     *   )
     * )
     */
    public function find(TimelineStoreRequest $request)
    {
        try{
            /* get input params from request */
            $topicNumber = (int) $request->input('topic_num');
            $algorithm = $request->input('algorithm');
            /** Get Cron Run date from .env file and make timestring */
            $cronDate = UtilHelper::getCronRunDateString();

            // get the timeline tree from mongoDb
            $start = microtime(true);
            $conditions = TimelineService::getConditions($topicNumber, $algorithm);
            $mongoTree = TimelineRepository::findTimeline($conditions);

            // First check the topic exist in database
            $asOfTime= time();
            $topicExistInMySql = TopicService::checkTopicInMySql($topicNumber,$asOfTime);

            /* If the timeline is not in mongo for that asOfTime, then create in mongo and return the timeline */
            if ((!$mongoTree || !count($mongoTree)) && $topicExistInMySql) {
                
                if(Artisan::call('timeline:all '.$topicNumber.' '.$algorithm)){
                    $mongoTree = TimelineRepository::findTimeline($conditions);
                }             
            }
       
            if($mongoTree && count($mongoTree)) {
                $tree = collect([$mongoTree[0]]);
            }
            else{
                $tree =[];
            }
 
            $end = microtime(true);
            $time = $end - $start;
            $response = new TimelineResource($tree);
            $collectionToJson = json_encode($response, true);
            $responseArray = json_decode($collectionToJson, true);
            // Below code is for checking the requested camp number is created on the asOfTime.
            if(array_key_exists('data', $responseArray) && count($responseArray['data'])) {
                // sorting arraY
                /*uksort($responseArray, function($a, $b) {
                    $aParts = explode('_', $a);
                    $bParts = explode('_', $b);
                    
                    $aMiddle = isset($aParts[1]) ? $aParts[1] : '';
                    $bMiddle = isset($bParts[1]) ? $bParts[1] : '';
                
                    return $aMiddle <=> $bMiddle;
                });*/
                // loop through array
                foreach($responseArray['data'] as $key => $item){
                    // unset them
                    unset($item["_id"]);
                    unset($item["topic_id"]);
                    unset($item["algorithm_id"]);
                    unset($item["updated_at"]);
                    unset($item["created_at"]);

                    $responseArray['data']=$item;
                }
                $response = $responseArray;
                
            }
        
            Log::info("Time via find method: " . $time);
            //Log::info($response);die;
            return $response;
        } 
        catch (Throwable $e) {
            $errResponse = UtilHelper::exceptionResponse($e, $request->input('tracing') ?? false);
            return response()->json($errResponse, 500);
        }
    }
}
