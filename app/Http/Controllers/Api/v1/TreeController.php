<?php

namespace App\Http\Controllers\Api\v1;

use App\Events\IncreaseTopicViewCountEvent;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\TreeStoreRequest;
use App\Http\Resources\TreeResource;
use DateTimeHelper;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use TreeRepository;
use TreeService;
use UtilHelper;
use App\Model\v1\Topic;
use App\Model\v1\Camp;
use App\Model\v1\Statement;
use App\Model\v1\TopicView;
use App\Services\CampService;
use App\Services\TopicService;
use Throwable;
use App\Services\AlgorithmService;
use Exception;

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

        $topicNumber        = (int) $request->input('topic_num');
        $algorithm          = $request->input('algorithm');
        $asOfTime           = (int) $request->input('asofdate');
        $updateAll          = (int) $request->input('update_all', 0);
        $model_id           = $request->input('model_id') ?? NULL;
        $model_type         = $request->input('model_type') ?? NULL;
        $job_type           = $request->input('job_type') ?? NULL;
        $camp_num           = (int) $request->input('camp_num');
        $event_type         = $request->input('event_type') ?? NULL;
        $pre_LiveId         = $request->input('pre_LiveId') ?? NULL;

        $start = microtime(true);
        $currentTime = time();

        /**
         * Update each topic grace period where grace period duration is completed
         */
        $topics = Topic::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->orderBy('submit_time', 'asc')->get();

        if ($topics->count() > 0) {
            foreach ($topics as $topic) {
                $submittedTime = $topic->submit_time;
                $gracePeriodEndTime = $submittedTime + env('COMMIT_TIME_DELAY_IN_SECONDS');
                if ($currentTime > $gracePeriodEndTime) {
                    // $topic->submit_time = time();
                    // $topic->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                    // $topic->grace_period = 0;
                    // $topic->update();

                    if(!self::commitTheChange($topic->id, 'topic')) {
                        throw new Exception('Authentication Issue!', 401);
                    }
                }
            }
        }


        /**
         * Update each camp grace period where grace period duration is completed
         */
        $camps = Camp::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->orderBy('submit_time', 'asc')->get();

        if ($camps->count() > 0) {
            foreach ($camps as $camp) {
                $submittedTime = $camp->submit_time;
                $gracePeriodEndTime = $submittedTime + env('COMMIT_TIME_DELAY_IN_SECONDS');
                if ($currentTime > $gracePeriodEndTime) {
                    // $camp->submit_time = time();
                    // $camp->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                    // $camp->grace_period = 0;
                    // $camp->update();

                    if(!self::commitTheChange($camp->id, 'camp', $camp->old_parent_camp_num, $camp->parent_camp_num)) {
                        throw new Exception('Authentication Issue!', 401);
                    }
                }
            }
        }

        /**
         * Update each statement grace period where grace period duration is completed
         */
        $statements = Statement::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->orderBy('submit_time', 'asc')->get();

        if ($statements->count() > 0) {
            foreach ($statements as $statement) {
                $submittedTime = $statement->submit_time;
                $gracePeriodEndTime = $submittedTime + env('COMMIT_TIME_DELAY_IN_SECONDS');
                if ($currentTime > $gracePeriodEndTime) {
                    // $statement->submit_time = time();
                    // $statement->go_live_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
                    // $statement->grace_period = 0;
                    // $statement->update();

                    if(!self::commitTheChange($statement->id, 'statement')) {
                        throw new Exception('Authentication Issue!', 401);
                    }
                }
            }
        }

        $tree = TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request);

        $end = microtime(true);
        $time = $end - $start;
        
        /// Check the job is for 24 hour
        /// check all id and hit the changeToAgree api
        if($job_type == "live-time-job") {
            if(!empty($model_id) && !empty($model_type)) {
                $this->agreeToChange($model_id, $topicNumber, $camp_num, $event_type, $pre_LiveId, $model_type);
            }
        }

        Log::info("Time via store method: " . $time);

        return new TreeResource(array($tree));
    }

    private function agreeToChange($changeId, $topic_num, $camp_num, $event_type, $pre_LiveId, $change_for = "") {
        $requestBody = [
            'record_id'             => $changeId,
            'topic_num'             => $topic_num,
            'camp_num'              => $camp_num,
            'change_for'            => $change_for,
            'event_type'            => $event_type,
            'pre_LiveId'            => $pre_LiveId,
            "called_from_service"   => true
        ];

        $endpoint = env('API_APP_URL') . "/" . env('API_AGREE_CHANGE_FOR_LIVE');

        $headers = [];
        $headers[] = 'Content-Type:multipart/form-data';
        $headers[] = 'Authorization:Bearer: ' . env('API_TOKEN') . '';

        $response = UtilHelper::curlExecute('POST', $endpoint, $headers, $requestBody);

        if(isset($response)) {
            $checkRes = json_decode($response, true);
            Log::info('AgreeTheChange => ' . json_encode($checkRes));
            if(array_key_exists("status_code", $checkRes) && $checkRes["status_code"] == 401) {
                Log::error("agreeTheChange => Unauthorized action.");
                throw new Exception('Authentication Issue!', 401);
                return false;
            }
        }
        return true;
    }

    private function commitTheChange($id, $type, $oldParentCampNum = null, $parentCampNum = null) {
        $requestBody = [
            "id" => $id,
            "type" => $type,
            "called_from_service" => true,
            "old_parent_camp_num" => $oldParentCampNum,
            "parent_camp_num" => $parentCampNum
        ];

        $endpoint = env('API_APP_URL') . "/" . env('API_COMMIT_CHANGE');

        //$headers = array('Content-Type:multipart/form-data');
        $headers = []; // Prepare headers for request
        $headers[] = 'Content-Type:multipart/form-data';
        $headers[] = 'Authorization:Bearer: ' . env('API_TOKEN') . '';

        $response = UtilHelper::curlExecute('POST', $endpoint, $headers, $requestBody);
        if(isset($response)) {
            $checkRes = json_decode($response, true);
            Log::info('CommitTheChange => ' . json_encode($checkRes));
            if(array_key_exists("status_code", $checkRes) && $checkRes["status_code"] == 401) {
                Log::error("commitTheChange => Unauthorized action.");
                throw new Exception('Authentication Issue!', 401);
                return false;
            }
        }
        return true;
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
        try {
            /* get input params from request */
            $topicNumber = (int) $request->input('topic_num');
            $algorithm = $request->input('algorithm');

            
            $asOf = $request->input('asOf');
            $asOfTime = ($asOf=="default" || $asOf=="review") ? time() : ceil($request->input('asofdate'));
            $updateAll = (int) $request->input('update_all', 0);
            $fetchTopicHistory =  $request->input('fetch_topic_history');

            $asOfDate = Helpers::getStartOfTheDay($asOfTime);
            $campNumber = (int) $request->input('camp_num', 1);
            $topicId = $topicNumber . '_' . $campNumber;

            // get the tree from mongoDb
            $start = microtime(true);

            // If tree:all command is running, fetch tree from MySQL
            $commandStatement = "php artisan tree:all";
            $commandSignature = "tree:all";

            $algorithms =  AlgorithmService::getAlgorithmKeyList("tree");

            $commandStatus = UtilHelper::getCommandRuningStatus($commandStatement, $commandSignature);

            if (in_array($algorithm, $algorithms) && !($fetchTopicHistory) && !$commandStatus) {

                $conditions = TreeService::getConditions($topicNumber, $algorithm, $asOfDate);

                /**
                 * Fetch topic tree on the basis of jobs in queue or processed ones
                 * If there is any job exists in jobs table, either model_id has topic number (for 2.0) or unique id has topic number (for 3.0)
                 * then topic tree isn't updated in Mongo yet -- Get tree from MySQL Database
                 * If there is not job exists in jobs table, then there is no pending jobs -- need to check latest processed job status
                 * If latest processed job status is failed, then topic tree isn't updated in Mongo yet -- Get tree from MySQL Database
                 * If latest processed job status is success, and tree found, then topic tree has been updated in Mongo -- Get tree from Mongo Database
                 * If latest processed job status is success, and tree not found, then topic tree hasn't been updated in Mongo -- Get tree from MySQL Database
                 * If there is no processed job found for specific topic -- Get tree from Mongo
                 */

                $isLastJobPending = \DB::table('jobs')->where('queue', env('QUEUE_NAME'))->where('model_id', $topicNumber)->orWhere('unique_id', $topicId)->first();
                $latestProcessedJobStatus  = \DB::table('processed_jobs')->where('topic_num', $topicNumber)->orderBy('id', 'desc')->first();

                // for now we will get topic in review record from database, because in mongo tree we only have default herarchy currently.
                if ($isLastJobPending || $asOf == "review") {
                    $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                } else {
                    if (($latestProcessedJobStatus && $latestProcessedJobStatus->status == 'Success') || !$latestProcessedJobStatus) {
                        #MongoDBRefactoring -- Find the latest tree in mongo
                        $mongoTree = TreeRepository::findLatestTree($conditions);

                        if ($mongoTree && count($mongoTree)) {
                            // If requested asOfDate < The latest version asOfDate of tree in Mongo...
                            if ($asOfDate < $mongoTree[0]->as_of_date) {

                                // Now check the tree exists in mongo for requested asOfDate..
                                $mongoTree = TreeRepository::findTree($conditions);
                                /* If the tree is not in mongo for that asOfDate, then create in mongo and
                                return the tree */
                                if ((!$mongoTree || !count($mongoTree))) {
                                    // First check the topic exist in database, then we can run upsertTree.
                                    $topicExistInMySql = TopicService::checkTopicInMySql($topicNumber, $asOfTime);

                                    if ($topicExistInMySql) {
                                        $mongoTree = array(TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                                    }
                                }
                            }

                            if ($mongoTree && count($mongoTree)) {
                                $tree = collect([$mongoTree[0]['tree_structure']]);
                                if (!$tree[0][1]['title'] || ($request->asOf == "review" && !$tree[0][1]['review_title'])) {
                                    $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                                }
                            } else {
                                $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                            }
                        } else {
                            $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                        }
                    } else {
                        $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                    }
                }
            } else {
                //TODO: shift latest mind_expert algorithm from canonizer 2.0 from getSupportCountFunction
                $tree = array(TreeService::getTopicTreeFromMysql($topicNumber, $algorithm, $asOfTime, $updateAll, $request, $fetchTopicHistory));
            }

            $end = microtime(true);
            $time = $end - $start;

            $response = new TreeResource($tree);
            $collectionToJson = json_encode($response, true);
            $responseArray = json_decode($collectionToJson, true);

            // Below code is for checking the requested camp number is created on the asOfTime.
            if (array_key_exists('data', $responseArray) && count($responseArray['data'])) {
                if ($asOf == 'bydate') {

                    $topicCreatedDate = TopicService::getTopicCreatedDate($topicNumber);
                    $campCreatedDate = CampService::getCampCreatedDate($campNumber, $topicNumber);

                    $responseArray['data'][0][1]['is_valid_as_of_time']  = $asOfTime >= $topicCreatedDate ? true : false;

                    if ($campNumber != 1 && $asOfTime < $campCreatedDate) {
                        $campInfo = [
                            'camp_exist' => $asOfDate < $campCreatedDate ? false : true,
                            'created_at' => $campCreatedDate
                        ];
                        array_push($responseArray['data'], $campInfo);
                    }
                }
                $responseArray['data'][0][1] = array_merge($responseArray['data'][0][1], ['collapsedTreeCampIds' => array_reverse(Helpers::renderParentsCampTree($topicNumber, $campNumber))]);
            }

            if ($request->has('view')) {
                event(new IncreaseTopicViewCountEvent($topicNumber, $campNumber, ceil($request->input('asofdate')), $request->view));
            }

            $responseArray['data'][0][1]['camp_views'] = intval(Helpers::getCampViewsByDate($topicNumber, $campNumber));
            $response = $responseArray;
            
            return $response;
        } catch (Throwable $e) {
            $errResponse = UtilHelper::exceptionResponse($e, $request->input('tracing') ?? false);
            return response()->json($errResponse, 500);
        }
    }
}
