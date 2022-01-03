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
use AlgorithmService;
use Illuminate\Support\Facades\Log;

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
        $asOfTime = $request->input('asofdate');
        $updateAll = $request->input('update_all', 0);

        $algorithms =  AlgorithmService::getCacheAlgorithms($updateAll, $algorithm);

        foreach ($algorithms as $algo) {
            try {

                $tree = CampService::prepareCampTree($algo, $topicNumber, $asOfTime);
                $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);

                //get date string from timestamp
                $asOfDate = DateTimeHelper::getAsOfDate($request);
                $mongoArr = TreeService::prepareMongoArr($tree, $topic, $request, $asOfDate, $algo);
                $conditions =  TreeService::getConditions($topicNumber, $algo, $asOfDate);
            } catch (CampTreeException | CampDetailsException | CampTreeCountException | CampSupportCountException | CampURLException | \Exception $th) {
                return ["data" => [], "code" => 401, "success" => false, "error" => $th->getMessage()];
            }

            $tree = TreeRepository::upsertTree($mongoArr, $conditions);
        }

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
        $asOfDate =  DateTimeHelper::getAsOfDate($request);

        $conditions =  TreeService::getConditions($topicNumber, $algorithm, $asOfDate);
        $tree =  TreeRepository::findTree($conditions);

        return new TreeResource($tree);
    }
}
