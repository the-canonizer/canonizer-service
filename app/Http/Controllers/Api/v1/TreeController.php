<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TreeStoreRequest;
use CampService;
use TreeService;
use TreeRepository;
use DateTimeHelper;
use TopicService;
use App\Http\Resources\TreeResource;
use App\Exceptions\Camp\CampTreeException;
use App\Exceptions\Camp\CampURLException;
use App\Exceptions\Camp\CampDetailsException;
use App\Exceptions\Camp\CampSupportCountException;
use App\Exceptions\Camp\CampTreeCountException;

class TreeController extends Controller
{

    /**
     * Store a new tree.
     *
     * @param  TreeStoreRequest  $request
     * @return Response
     */

    public function store(TreeStoreRequest $request)
    {
        /* get input params from request */
        $topicNumber = $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOfTime =  DateTimeHelper::getAsOfTime($request);

        try {
            $tree = CampService::prepareCampTree($algorithm, $topicNumber, $asOfTime);
            $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);
            $mongoArr = TreeService::prepareMongoArr($tree, $topic, $request, $asOfTime);
            $conditions =  TreeService::getConditions($topicNumber, $algorithm, $asOfTime);
        } catch (\Exception | CampTreeException | CampDetailsException | CampTreeCountException | CampSupportCountException | CampURLException $th) {
            return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
        }

        $tree = TreeRepository::upsertTree($mongoArr, $conditions);

        return new TreeResource(array($tree));
    }


    /**
     * get a tree.
     *
     * @param  TreeStoreRequest  $request
     * @return Response
     */

    public function find(TreeStoreRequest $request)
    {
        /* get input params from request */
        $topicNumber = $request->input('topic_num');
        $algorithm = $request->input('algorithm');
        $asOfTime =  DateTimeHelper::getAsOfTime($request);

        $conditions =  TreeService::getConditions($topicNumber, $algorithm, $asOfTime);
        $tree =  TreeRepository::findTree($conditions);

        return new TreeResource($tree);
    }
}
