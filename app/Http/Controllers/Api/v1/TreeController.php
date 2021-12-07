<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\TreeStoreRequest;
use App\Services\CampService;
use App\Repository\Tree\TreeRepository;
use App\Services\TreeService;

class TreeController extends Controller
{

    protected $campService;
    protected $treeService;
    protected $treeRepository;

    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct(
        TreeRepository $treeRepository,
        CampService $campService,
        TreeService $treeService
    ) {
        $this->treeRepository =  $treeRepository;
        $this->campService = $campService;
        $this->treeService =  $treeService;
    }


    /**
     * Store a new tree.
     *
     * @param  Request  $request
     * @return Response
     */

    public function store(TreeStoreRequest $request)
    {

        $topicNumber = $request->input('topic_num');
        $algorithm = $request->input('algorithm');

        $tree = $this->campService->prepareCampTree($algorithm, $topicNumber);
        $topic =  $this->campService->getAgreementTopic($topicNumber, $request);
        $mongoArr = $this->treeService->prepareMongoArr($tree, $topic, $request);

        if ($this->treeRepository->createTree($mongoArr)) {
            return response()->json(["code" => 200, "success" => true]);
        }

        return response()->json(["code" => 400, "success" => false], 400);
    }
}
