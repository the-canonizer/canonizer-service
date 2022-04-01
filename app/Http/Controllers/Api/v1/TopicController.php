<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Model\v1\Tree;
use CampService;
use DateTimeHelper;
use Illuminate\Http\Request;
use TopicService;
use UtilHelper;

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
        /* get input params from request */
        $pageNumber = $request->input('page_number');
        $pageSize = $request->input('page_size');
        $namespaceId = $request->input('namespace_id') !== "" ? (int) $request->input('namespace_id') : $request->input('namespace_id');
        $asofdateTime = (int) $request->input('asofdate');
        $algorithm = $request->input('algorithm');
        $search = $request->input('search');
        $asof = $request->input('asof');
        $filter = (float) $request->input('filter') ?? null;

        $asofdate = DateTimeHelper::getAsOfDate($asofdateTime);
        $skip = ($pageNumber - 1) * $pageSize;

        /** Get Cron Run date from .env file and make timestring */
        $cronDate = UtilHelper::getCronRunDateString();

        /* if $asofdate is greater then cron run date then get topics
         * with score from mongodb instance else fetch Topics with Score
         * from Mysql
         */
        if (($asofdate >= $cronDate) && ($algorithm == 'blind_popularity' || $algorithm == "mind_experts")) {

            $totalTopics = TopicService::getTotalTopics($namespaceId, $asofdate, $algorithm, $filter, $search);
            $numberOfPages = UtilHelper::getNumberOfPages($totalTopics, $pageSize);
            $topics = TopicService::getTopicsWithScore($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $search);
        } else {

            /*  search & filter functionality */
            $topics = CampService::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdateTime, $namespaceId, $search);
            $topics = TopicService::sortTopicsBasedOnScore($topics, $algorithm, $asofdateTime);

            /** filter the collection if filter parameter */
            if (isset($filter) && $filter != '' && $filter != null) {
               $topics = TopicService::filterTopicCollection($topics, $filter);
            }

            /** total pages */
            $totalTopics = CampService::getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdate, $namespaceId, $search, true);
            $numberOfPages = UtilHelper::getNumberOfPages($totalTopics, $pageSize);
        }

        return new TopicResource($topics, $numberOfPages);

    }
}
