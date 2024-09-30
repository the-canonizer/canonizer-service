<?php

namespace App\Http\Controllers\Api\v2;

use App\Facades\Helpers\UtilHelperFacade;
use App\Facades\Services\AlgorithmServiceFacade;
use App\Facades\Services\CampServiceFacade;
use App\Facades\Services\TopicServiceFacade;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RemoveTopicsRequest;
use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Models\v1\Tag;
use App\Models\v1\Timeline;
use App\Models\v1\Tree;
use App\Models\v2\Nickname;
use App\Models\v2\Statement;
use App\Models\v2\TopicView;
use Throwable;

class TopicController extends Controller
{
    /**
     * @OA\Post(path="/topic/getAll",
     *   tags={"topics","trees"},
     *   summary="Get topics with pagination",
     *   description="This api is used to get topics depends on page size pass in request",
     *   operationId="getAllTopics",
     *
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get topics",
     *
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *
     *           @OA\Schema(
     *
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
     *
     *                             @OA\JsonContent(
     *                                 type="array",
     *
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
     *
     *                             @OA\JsonContent(
     *                                 type="array",
     *
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
     *
     *   @OA\Response(response=404, description="Topics not found",
     *
     *                @OA\JsonContent(
     *                                 type="array",
     *
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
     * Retrieves all topics based on the provided request parameters.
     *
     * @param  TopicRequest  $request  The request object containing the input parameters.
     * @return TopicResource The resource containing the retrieved topics.
     *
     * @throws Throwable If an error occurs during the execution of the function.
     */
    public function getAll(TopicRequest $request)
    {
        try {
            /* get input params from request */
            $pageNumber = $request->input('page_number');
            $pageSize = $request->input('page_size');

            $namespaceId = $request->input('namespace_id') !== '' ? (int) $request->input('namespace_id') : $request->input('namespace_id');
            $asofdateTime = (int) $request->input('asofdate'); // Store actual date time in this variable

            $algorithm = $request->input('algorithm');
            $search = $request->input('search');

            $asof = $request->input('asof');
            $filter = (float) $request->input('filter') ?? null;

            $nickNameIds = $request->input('user_email') ? Helpers::getNickNamesByEmail($request->input('user_email')) : [];

            $today = Helpers::getStartOfTheDay(time()); // Store start of today in this variable

            $skip = ($pageNumber - 1) * $pageSize;

            $archive = ($request->has('is_archive')) ? $request->input('is_archive') : 0;
            $totalCount = 0;
            $sort = ($request->has('sort')) ? $request->input('sort') : false;
            $page = $request->input('page') ?: 'home';
            $topic_tags = $request->input('topic_tags') ?: [];

            /**
             * If asofdate is greater then cron run date then get topics from Mongo else fetch from MySQL or
             * Check if tree:all command is running in background
             * Then command is in process of creating all topics trees in Mongo database (Mongo is not updated)
             * Fetch topics from MySQL (updated database)
             */
            $commandStatement = 'php artisan tree:all';
            $commandSignature = 'tree:all';

            $commandStatus = UtilHelperFacade::getCommandRuningStatus($commandStatement, $commandSignature);
            $algorithms = (array) AlgorithmServiceFacade::getAlgorithmKeyList('tree');

            // Only get data from MongoDB if asOfDate >= $today's start date #MongoDBRefactoring
            $topicsFoundInMongo = Tree::count();

            if ($asofdateTime >= $today && $topicsFoundInMongo && ! $commandStatus && in_array($algorithm, $algorithms)) {
                $topics = TopicServiceFacade::getTopicsWithScore($namespaceId, $today, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asof, $archive, $sort, $page, $topic_tags);
                extract($topics);
            } else {
                /*  search & filter functionality */
                $topics = CampServiceFacade::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $nickNameIds, $search, false, $archive, $sort, $topic_tags);
                if ($page === 'browse') {
                    $totalCount = CampServiceFacade::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $nickNameIds, $search, true, $archive, $sort, $topic_tags);
                }
                $topics = TopicServiceFacade::sortTopicsBasedOnScore($topics, $algorithm, $asofdateTime, $page);

                /** filter the collection if filter parameter */
                if (isset($filter) && $filter != '' && $filter != null) {
                    $topics = TopicServiceFacade::filterTopicCollection($topics, $filter);
                }
            }

            $topicViews = TopicView::getTopicViewCounts(collect($topics)->pluck('topic_id')->all())->mapWithKeys(function ($item) {
                return [$item['topic_num'] => $item['view_count']];
            })->all();

            if (is_array($topics)) {
                $topics = array_values($topics);
            }

            foreach ($topics as $key => $value) {
                if (is_object($value)) {
                    $topics[$key]->camp_views = intval($topicViews[$value->topic_id] ?? 0);

                    $topics[$key]->tags = Tag::whereIn('id', function ($query) use ($value) {
                        $query->from('topics_tags')->select('tag_id')->where('topic_num', $value->topic_id)->get();
                    })->where('is_active', 1)->get();

                    if ($page === 'browse') {
                        $topics[$key]->statement = Statement::getLiveStatementText($value->topic_id, 1);
                        foreach ($topics[$key]->tree_structure[1]['support_tree'] as $supportKey => $support) {
                            $topics[$key]->tree_structure[1]['support_tree'][$supportKey]['user'] = Nickname::with('user:id,first_name,last_name,email,profile_picture_path')->find($support['nick_name_id'])->user;
                        }
                    }
                } elseif (is_array($value)) {  // MongoDB Case
                    $topics[$key]['camp_views'] = intval($topicViews[$value['topic_id']] ?? 0);

                    $topics[$key]['tags'] = Tag::whereIn('id', function ($query) use ($value) {
                        $query->from('topics_tags')->select('tag_id')->where('topic_num', $value['topic_id'])->get();
                    })->where('is_active', 1)->get();

                    if ($page === 'browse') {
                        $topics[$key]['statement'] = Statement::getLiveStatementText($value['topic_id'], 1);
                        foreach ($topics[$key]['tree_structure'][1]['support_tree'] as $supportKey => $support) {
                            $topics[$key]['tree_structure'][1]['support_tree'][$supportKey]['user'] = Nickname::with('user:id,first_name,last_name,email,profile_picture_path')->find($support['nick_name_id'])->user;
                        }
                    }
                }
            }

            return new TopicResource($topics, $totalCount);
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
     *
     *   @OA\RequestBody(
     *       required=true,
     *       description="Remove Topics",
     *
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *
     *           @OA\Schema(
     *
     *                 @OA\Property(
     *                     property="topic_numbers",
     *                     required=true,
     *                     type="integer|array",
     *                 )
     *         )
     *   ),
     *
     *   @OA\Response(response=200,description="successful operation",
     *
     *                             @OA\JsonContent(
     *                                 type="array",
     *
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
     *
     *                             @OA\JsonContent(
     *                                 type="array",
     *
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
     *
     *   @OA\Response(response=404, description="Topics not found",
     *
     *                @OA\JsonContent(
     *                                 type="array",
     *
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
     * @return Response
     */
    public function removeCacheSpecificTopics(RemoveTopicsRequest $request)
    {
        try {
            $response = [
                'status_code' => 404,
                'message' => 'Not found',
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
