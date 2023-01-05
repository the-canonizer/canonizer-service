<?php

namespace App\Http\Controllers\Api\v1;

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
use App\Services\CampService;
use App\Services\TopicService;
use Throwable;

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
        $currentTime = time();

        /**
         * Update each topic grace period where grace period duration is completed
         */
        $topics = Topic::select('id', 'submit_time')->where('topic_num', $topicNumber)->where('grace_period', '1')->where('objector_nick_id', NULL)->get();

        if($topics->count() > 0) {
            foreach($topics as $topic) {
                $submittedTime = $topic->submit_time;
                $gracePeriodEndTime = $submittedTime + (60 * 60);
                if ($currentTime > $gracePeriodEndTime) {
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

        if ($statements->count() > 0) {
            foreach ($statements as $statement) {
                $submittedTime = $statement->submit_time;
                $gracePeriodEndTime = $submittedTime + (60 * 60);
                if ($currentTime > $gracePeriodEndTime) {
                    $statement->grace_period = 0;
                    $statement->update();
                }
            }
        }

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
        try {
            /* get input params from request */
            $topicNumber = (int) $request->input('topic_num');
            $algorithm = $request->input('algorithm');

            $asOfTime = (int) $request->input('asofdate');
            $asOf = $request->input('asOf');
            $updateAll = (int) $request->input('update_all', 0);
            $fetchTopicHistory =  $request->input('fetch_topic_history');

            $asOfDate = Helpers::getStartOfTheDay($asOfTime);

            $campNumber = (int) $request->input('camp_num', 1);
            $topicId = $topicNumber . '_' . $campNumber;

            // get the tree from mongoDb
            $start = microtime(true);

            if (in_array($algorithm, ['blind_popularity', 'mind_experts'])) {

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
                                if((!$mongoTree || !count($mongoTree))) {
                                    // First check the topic exist in database, then we can run upsertTree.
                                    $topicExistInMySql = TopicService::checkTopicInMySql($topicNumber);
                                    
                                    if ($topicExistInMySql) {
                                        $mongoTree = array(TreeService::upsertTree($topicNumber, $algorithm, $asOfTime, $updateAll, $request));
                                    }
                                }
                            }

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
            if (array_key_exists('data', $responseArray) && count($responseArray['data']) && $asOf == 'bydate' && $campNumber != 1) {
                $campCreatedDate = CampService::getCampCreatedDate($campNumber, $topicNumber);

                if ($asOfTime < $campCreatedDate) {
                    $campInfo = [
                        'camp_exist' => $asOfDate < $campCreatedDate ? false : true,
                        'created_at' => $campCreatedDate
                    ];
                    array_push($responseArray['data'], $campInfo);
                }

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
