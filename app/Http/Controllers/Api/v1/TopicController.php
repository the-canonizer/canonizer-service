<?php

namespace App\Http\Controllers\Api\v1;

use App\Facades\Helpers\UtilHelperFacade;
use App\Facades\Services\{AlgorithmServiceFacade, CampServiceFacade, TopicServiceFacade};
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\{RemoveTopicsRequest, TopicRequest};
use App\Http\Resources\TopicResource;
use App\Models\v1\{Timeline, Tree};
use Throwable;

class TopicController extends Controller
{

    /**
     * Get all topics based on the provided parameters.
     *
     * @param TopicRequest $request The request containing input parameters
     * @throws Throwable An exception if an error occurs
     * @return TopicResource The resource containing the retrieved topics
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

            $sort = ($request->has('sort')) ?  $request->input('sort') : false;

            /**
             * If asofdate is greater then cron run date then get topics from Mongo else fetch from MySQL or
             * Check if tree:all command is running in background
             * Then command is in process of creating all topics trees in Mongo database (Mongo is not updated)
             * Fetch topics from MySQL (updated database)
             */
            $commandStatement = "php artisan tree:all";
            $commandSignature = "tree:all";

            $commandStatus = UtilHelperFacade::getCommandRuningStatus($commandStatement, $commandSignature);
            $algorithms =  AlgorithmServiceFacade::getAlgorithmKeyList("tree");

            // if (in_array($algorithm, $algorithms) && !$commandStatus) {

            // Only get data from MongoDB if asOfDate >= $today's start date #MongoDBRefactoring
            $topicsFoundInMongo = Tree::count();
            if ($asofdateTime >= $today && $topicsFoundInMongo && !$commandStatus && in_array($algorithm, $algorithms)) {
                $topics = TopicServiceFacade::getTopicsWithScore($namespaceId, $today, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asof, $archive, $sort);
            } else {

                /*  search & filter functionality */
                $topics = CampServiceFacade::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $nickNameIds, $search, '', $archive, $sort);
                $topics = TopicServiceFacade::sortTopicsBasedOnScore($topics, $algorithm, $asofdateTime);

                /** filter the collection if filter parameter */
                if (isset($filter) && $filter != '' && $filter != null) {
                    $topics = TopicServiceFacade::filterTopicCollection($topics, $filter);
                    /* We will count the filtered topic here, because the above totalTopics is without filter */
                }
            }

            return new TopicResource($topics);
        } catch (Throwable $th) {
            $errorResponse = UtilHelperFacade::exceptionResponse($th, $request->input('tracing') ?? false);
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
                $removeTree = Tree::whereIn('topic_id', $request->topic_numbers)->delete();

                $removeTimeline = Timeline::whereIn('topic_id', $request->topic_numbers)->delete();

                if ($removeTree && $removeTimeline) {
                    $response['status_code'] = 200;
                    $response['message'] = 'Tree and Timeline cache removed for requested topics';
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
