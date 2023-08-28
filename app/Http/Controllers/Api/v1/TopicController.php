<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RemoveTopicsRequest;
use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Services\AlgorithmService;
use App\Model\v1\Tree;
use App\Model\v1\Nickname;
use CampService;
use DateTimeHelper;
use Illuminate\Http\Request;
use TopicService;
use UtilHelper;
use Throwable;
use Illuminate\Http\Response;

class TopicController extends Controller
{
    /**
     * @OA\Post(path="/topic/getAll",
     *   tags={"topics","trees"},
     *   summary="Get topics with pagination",
     *   description="This api is used to get topics depends on page size pass in request",
     *   operationId="getAllTopics",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get topics",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *                 @OA\Property(
     *                     property="page_number",
     *                     description="current page number",
     *                     required=true,
     *                     type="integer",
     *                     format="int32"
     *                 ),
     *                 @OA\Property(
     *                     property="page_size",
     *                     description="how many records required in api",
     *                     required=true,
     *                     type="integer",
     *                     format="int32"
     *                 ),
     *                @OA\Property(
     *                     property="namespace_id",
     *                     description="namespace id",
     *                     required=false,
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
     *                     property="asofdate",
     *                     description="current timestamp or only datetime string",
     *                     required=true,
     *                     type="integer",
     *                     format="int32"
     *                 ),
     *                @OA\Property(
     *                     property="search",
     *                     description="search type",
     *                     required=true,
     *                     type="string"
     *                 ),
     *                @OA\Property(
     *                     property="filter",
     *                     description="select filter",
     *                     required=false,
     *                     type="float"
     *                 ),
     *                @OA\Property(
     *                     property="user_email",
     *                     description="user email for returning only user topics",
     *                     required=false,
     *                     type="string"
     *                 )
     *         )
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
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="number_of_pages",
     *                                         type="integer"
     *                                    ),
     *                                   @OA\Items(
     *                                         name="error",
     *                                         type="string"
     *                                    )
     *                                 )
     *                            )
     *
     *   @OA\Response(response=400, description="Exception occurs while fetching topics",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="errors",
     *                                         type="array"
     *                                    )
     *                                 )
     *                             )
     *   @OA\Response(response=404, description="Topics not found",
     *                @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="data",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="errors",
     *                                         type="array"
     *                                    )
     *                          )
     *                  )
     * )
     */

    /**
     * get all topics.
     *
     * @param  TopicRequest  $request
     * @return Response
     */

    public function getAll(TopicRequest $request)
    {
        try {
            /* get input params from request */
            $pageNumber = $request->input('page_number');
            $pageSize = $request->input('page_size');

            $namespaceId = $request->input('namespace_id') !== "" ? (int) $request->input('namespace_id') : $request->input('namespace_id');
            $asofdateTime = (int) $request->input('asofdate'); // Store actual date time in this variable

            $algorithm = $request->input('algorithm');
            $search = $request->input('search');

            $asof = $request->input('asof');
            $filter = (float) $request->input('filter') ?? null;

            $nickNameIds = $request->input('user_email') ? Helpers::getNickNamesByEmail($request->input('user_email')) : [];

            $today = Helpers::getStartOfTheDay(time()); // Store start of today in this variable

            $skip = ($pageNumber - 1) * $pageSize;

            $archive = ($request->has('is_archive')) ? $request->input('is_archive') : 0;
            /**
             * If asofdate is greater then cron run date then get topics from Mongo else fetch from MySQL or
             * Check if tree:all command is running in background
             * Then command is in process of creating all topics trees in Mongo database (Mongo is not updated)
             * Fetch topics from MySQL (updated database)
             */
            $commandStatement = "php artisan tree:all";
            $commandSignature = "tree:all";

            $commandStatus = UtilHelper::getCommandRuningStatus($commandStatement, $commandSignature);
            $algorithms =  AlgorithmService::getAlgorithmKeyList("tree");

            // if (in_array($algorithm, $algorithms) && !$commandStatus) {

            // Only get data from MongoDB if asOfDate >= $today's start date #MongoDBRefactoring
            $topicsFoundInMongo = Tree::count();
            if ($asofdateTime >= $today && $topicsFoundInMongo && !$commandStatus && in_array($algorithm, $algorithms)) {
                // $totalTopics = TopicService::getTotalTopics($namespaceId, $today, $algorithm, $filter, $nickNameIds, $search, $asof, $archive);
                // $numberOfPages = UtilHelper::getNumberOfPages($totalTopics, $pageSize);
                $topics = TopicService::getTopicsWithScore($namespaceId, $today, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asof, $archive);
            } else {

                /*  search & filter functionality */
                $topics = CampService::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $nickNameIds, $search, '', $archive);
                $topics = TopicService::sortTopicsBasedOnScore($topics, $algorithm, $asofdateTime);
                // $totalTopics = CampService::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdate, $namespaceId, $nickNameIds, $search, true, $archive);

                /** filter the collection if filter parameter */
                if (isset($filter) && $filter != '' && $filter != null) {
                    $topics = TopicService::filterTopicCollection($topics, $filter);
                    /* We will count the filtered topic here, because the above totalTopics is without filter */
                    // $totalTopics = $topics->count();
                }

                /** total pages */
                // $numberOfPages = UtilHelper::getNumberOfPages($totalTopics, $pageSize);
            }
            // } else {
            //     /*  search & filter functionality */
            //     $topics = CampService::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $nickNameIds, $search,'', $archive);
            //     $topics = TopicService::sortTopicsBasedOnScore($topics, $algorithm, $asofdateTime);
            //     // $totalTopics = CampService::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdate, $namespaceId, $nickNameIds, $search, true, $archive);

            //     /** filter the collection if filter parameter */
            //     if (isset($filter) && $filter != '' && $filter != null) {
            //         $topics = TopicService::filterTopicCollection($topics, $filter);
            //         /* We will count the filtered topic here, because the above totalTopics is without filter */
            //         // $totalTopics = $topics->count();
            //     }

            //     /** total pages */
            //     // $numberOfPages = UtilHelper::getNumberOfPages($totalTopics, $pageSize);
            // }

            return new TopicResource($topics);
        } catch (Throwable $th) {
            $errorResponse = UtilHelper::exceptionResponse($th, $request->input('tracing') ?? false);
            return response()->json($errorResponse, 500);
        }
    }

    /**
     * @OA\Post(path="/tree/remove-sandbox-tree",
     *   tags={"trees"},
     *   summary="Remove topics by ids",
     *   description="This api is used to remove specific topic trees in cache",
     *   operationId="removeCacheSpecificTopics",
     *   @OA\RequestBody(
     *       required=true,
     *       description="Remove Topics",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *                 @OA\Property(
     *                     property="topic_numbers",
     *                     required=true,
     *                     type="integer|array",
     *                 )
     *         )
     *   ),
     *
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                  @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                       @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    )
     *                                 )
     *                            )
     *
     *   @OA\Response(response=500, description="Exception occurs while removing topics",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                       @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    )
     *                                 )
     *                             )
     *   @OA\Response(response=404, description="Topics not found",
     *                @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                       @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    )
     *                          )
     *                  )
     * )
     */
    /**
     * Remove sandbox topics.
     *
     * @param  RemoveTopicsRequest  $request
     * @return Response
     */

    public function removeCacheSpecificTopics(RemoveTopicsRequest $request)
    {
        try {
            $response = [
                'status_code' => 404,
                'message' => 'Not found'
            ];

            if ($request->has('topic_numbers')) {
                $getTopics = Tree::whereIn('topic_id', $request->topic_numbers)->delete();

                if ($getTopics) {
                    $response['status_code'] = 200;
                    $response['message'] = 'Tree cache removed';
                }
            }
        } catch (Throwable $th) {
            $response['status_code'] = 500;
            $response['message'] = $th->getMessage();
        }

        // Return JSON response with status_code
        return response()->json($response, $response['status_code']);
    }
}
