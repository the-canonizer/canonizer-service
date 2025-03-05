<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RemoveTopicsRequest;
use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Services\AlgorithmService;
use App\Model\v1\Tree;
use App\Model\v1\Timeline;
use App\Model\v1\TopicView;
use CampService;
use TopicService;
use UtilHelper;
use Throwable;
use Illuminate\Support\Facades\DB;

class TopicController extends Controller
{
    /**
     * @OA\Post(
     *   path="/v1/topic/getAll",
     *   tags={"Topic"},
     *   summary="Get all latest trees",
     *   description="This API retrieves all latest trees from MongoDB and Database using various query parameters.",
     *   operationId="GetAllLatestTreesV1",
     * 
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="algorithm", type="string", example="blind_popularity", description="Algorithm to be used"),
     *       @OA\Property(property="asofdate", type="integer", example=1724311750, description="Timestamp representing the 'as of' date"),
     *       @OA\Property(property="namespace_id", type="string", example="1", description="Namespace identifier"),
     *       @OA\Property(property="page_number", type="integer", example=1, description="Page number for pagination"),
     *       @OA\Property(property="page_size", type="integer", example=15, description="Number of items per page"),
     *       @OA\Property(property="search", type="string", example="", description="Search term"),
     *       @OA\Property(property="filter", type="integer", example=0, description="Filter flag"),
     *       @OA\Property(property="asof", type="string", example="default", description="Asof parameter"),
     *       @OA\Property(property="user_email", type="string", example="", description="User email"),
     *       @OA\Property(property="is_archive", type="integer", example=0, description="Archive flag (0 for active, 1 for archived)"),
     *       @OA\Property(property="sort", type="boolean", example=false, description="Sort flag")
     *     )
     *   ),
     * 
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=200),
     *       @OA\Property(property="message", type="string", example="Success"),
     *       @OA\Property(property="error", type="string", nullable=true, example=null),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(
     *           property="topic",
     *           type="array",
     *           minItems=2,
     *           @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="string"),
     *             @OA\Property(property="as_of_date", type="integer"),
     *             @OA\Property(property="topic_score", type="number", format="float"),
     *             @OA\Property(property="topic_full_score", type="integer"),
     *             @OA\Property(property="topic_name", type="string"),
     *             @OA\Property(property="topic_id", type="integer"),
     *             @OA\Property(property="namespace_id", type="integer"),
     *             @OA\Property(property="algorithm_id", type="string"),
     *             @OA\Property(property="tree_structure", type="array",
     *               @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="review_title", type="string"),
     *               )
     *             ),
     *             @OA\Property(property="submitter_nick_id", type="integer"),
     *             @OA\Property(property="created_by_nick_id", type="integer"),
     *             @OA\Property(property="camp_views", type="integer"),
     *           )
     *         )
     *       )
     *     )
     *   ),
     * 
     *   @OA\Response(
     *     response=400,
     *     description="Exception occurs",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=400),
     *       @OA\Property(property="message", type="string", example="error"),
     *       @OA\Property(property="error", type="string", nullable=true, example="message"),
     *       @OA\Property(property="data", type="object", nullable=true)
     *     )
     *   )
     * )
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

            $commandStatus = UtilHelper::getCommandRuningStatus($commandStatement, $commandSignature);
            $algorithms =  AlgorithmService::getAlgorithmKeyList("tree");

            // if (in_array($algorithm, $algorithms) && !$commandStatus) {

            // Only get data from MongoDB if asOfDate >= $today's start date #MongoDBRefactoring
            $topicsFoundInMongo = Tree::count();
            if ($asofdateTime >= $today && $topicsFoundInMongo && !$commandStatus && in_array($algorithm, $algorithms)) {
                // $totalTopics = TopicService::getTotalTopics($namespaceId, $today, $algorithm, $filter, $nickNameIds, $search, $asof, $archive);
                // $numberOfPages = UtilHelper::getNumberOfPages($totalTopics, $pageSize);
                $topics = TopicService::getTopicsWithScore($namespaceId, $today, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asof, $archive, $sort);
            } else {

                /*  search & filter functionality */
                $topics = CampService::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $nickNameIds, $search, '', $archive, $sort);
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

            $topicViews = TopicView::select('topic_num', DB::raw('SUM(views) AS view_count'))
                ->whereIn('topic_num', collect($topics)->pluck('topic_id')->all())
                ->groupBy('topic_num')
                ->get()
                ->mapWithKeys(function ($item, int $key) {
                    return [$item['topic_num'] => $item['view_count']];
                })->all();

            foreach ($topics as $key => $value) {
                if (is_object($value)) {
                    $topics[$key]->camp_views = intval($topicViews[$value->topic_id] ?? 0);
                } elseif (is_array($value)) {
                    $topics[$key]['camp_views'] = intval($topicViews[$value['topic_id']] ?? 0);
                }
            }

            return new TopicResource($topics);
        } catch (Throwable $th) {
            $errorResponse = UtilHelper::exceptionResponse($th, $request->input('tracing') ?? false);
            return response()->json($errorResponse, 500);
        }
    }

    /**
     * @OA\Post(
     *   path="/v1/tree/remove-sandbox-tree",
     *   tags={"Tree"},
     *   summary="Remove sandbox tree",
     *   description="This API removes the sandbox tree.",
     *   operationId="RemoveSandboxTreeV1",
     * 
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="algorithm", type="string"),
     *       @OA\Property(property="topic_numbers", oneOf={
     *           @OA\Schema(type="array", @OA\Items(type="integer")),
     *           @OA\Schema(type="string")
     *         }
     *       )
     *     )
     *   ),
     * 
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=200),
     *       @OA\Property(property="message", type="string", example="Success")
     *     )
     *   ),
     * 
     *   @OA\Response(
     *     response=400,
     *     description="Exception occurs",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="status_code", type="integer", example=400),
     *       @OA\Property(property="message", type="string", example="error")
     *     )
     *   )
     * )
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
