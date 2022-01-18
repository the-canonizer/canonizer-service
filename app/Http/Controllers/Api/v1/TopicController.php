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

        Log::info($asofdate);

       $skip = ($pageNumber-1) * $pageSize;

       $totalTrees = TreeRepository::getTotalTrees($namespaceId, $asofdate, $algorithm);
       $numberOfPages = UtilHelper::getNumberOfPages($totalTrees, $pageSize);
       $trees = TreeRepository::getTreesWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize);

       return new TopicResource($trees, $numberOfPages);

    }
}
