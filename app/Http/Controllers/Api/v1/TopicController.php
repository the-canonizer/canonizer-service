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
     * get a tree.
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
        $filter = (float) $request->input('filter');

        $asofdate = DateTimeHelper::getAsOfDate($asofdate);

        Log::info($asofdate);

        $skip = ($pageNumber-1) * $pageSize;

        /** if filter param set then only get those topics which have score more than give filter */

        //get total trees
        $totalTrees = (isset($filter) && $filter!=null && $filter!='') ?
                      TreeRepository::getTotalTreesWithFilter($namespaceId, $asofdate, $algorithm, $filter):
                      TreeRepository::getTotalTrees($namespaceId, $asofdate, $algorithm);

        $totalTrees = count($totalTrees);

        $numberOfPages = UtilHelper::getNumberOfPages($totalTrees, $pageSize);

        //get topics with score
        $trees = (isset($filter) && $filter!=null && $filter!='') ?
                 TreeRepository::getTreesWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter):
                 TreeRepository::getTreesWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize);

        return new TopicResource($trees, $numberOfPages);

    }
}
