<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\TopicRequest;
use App\Model\v1\Tree;
use TreeRepository;
use UtilHelper;
use App\Http\Resources\TopicResource;
use Illuminate\Support\Facades\Log;
use DateTimeHelper;

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
        $namespaceId = (int)$request->input('namespace_id');
        $asofdate = (int)$request->input('asofdate');
        $algorithm = $request->input('algorithm');
        $search = $request->input('search');
        $filter = (float) $request->input('filter') ?? null;

        $asofdate = DateTimeHelper::getAsOfDate($asofdate);

        Log::info($asofdate);

        $skip = ($pageNumber-1) * $pageSize;

        /** if filter param set then only get those topics which have score more than give filter */

        //get total trees
        $totalTrees = (isset($filter) && $filter!=null && $filter!='') ?
                      TreeRepository::getTotalTreesWithFilter($namespaceId, $asofdate, $algorithm, $filter, $search):
                      TreeRepository::getTotalTrees($namespaceId, $asofdate, $algorithm, $search);

        $totalTrees = count($totalTrees);

        $numberOfPages = UtilHelper::getNumberOfPages($totalTrees, $pageSize);

        //get topics with score
        $trees = (isset($filter) && $filter!=null && $filter!='') ?
                 TreeRepository::getTreesWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $search):
                 TreeRepository::getTreesWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $search);

        return new TopicResource($trees, $numberOfPages);

    }
}
