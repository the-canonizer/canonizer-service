<?php

namespace App\Services;

use AlgorithmService;
use App\Exceptions\Camp\CampDetailsException;
use App\Exceptions\Camp\CampSupportCountException;
use App\Exceptions\Camp\CampTreeCountException;
use App\Exceptions\Camp\CampTreeException;
use App\Exceptions\Camp\CampURLException;
use App\Exceptions\Camp\AgreementCampsException;
use App\Model\v1\Camp;
use App\Model\v1\Support;
use App\Model\v1\TopicSupport;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use TopicService;

/**
 * Class CampService.
 *
 */
class CampService
{

    private $traversetempArray = [];
    private $sessionTempArray = [];

    const AGREEMENT_CAMP = "Agreement";

    /**
     * prepare Camp tree based on algorithm.
     *
     * @param string $algorithm
     * @param int $topicNumber
     * @param int $asOfTime
     * @param int $startCamp
     *
     * @return array $tree
     */

    public function prepareCampTree($algorithm, $topicNumber, $asOfTime, $startCamp = 1, $rootUrl = '', $nickNameId = null)
    {
        try {

            $this->traversetempArray = [];

            if (!Arr::exists($this->sessionTempArray, "topic-support-nickname-{$topicNumber}")) {

                $nickNameSupport = Support::where('topic_num', '=', $topicNumber)
                    ->where('delegate_nick_name_id', 0)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->groupBy('nick_name_id')
                    ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
                    ->get();

                $this->sessionTempArray["topic-support-nickname-{$topicNumber}"] = $nickNameSupport;
            }

            if (!Arr::exists($this->sessionTempArray, "topic-support-{$topicNumber}")) {

                $topicSupport = Support::where('topic_num', '=', $topicNumber)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->select(['support_order', 'camp_num', 'nick_name_id', 'delegate_nick_name_id', 'topic_num'])
                    ->get();

                $this->sessionTempArray["topic-support-{$topicNumber}"] = $topicSupport;
            }

            $topicChild = Camp::where('topic_num', '=', $topicNumber)
                ->where('camp_name', '!=', 'Agreement')
                ->where('objector_nick_id', '=', null)
                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time <= ' . $asOfTime . ' group by camp_num)')
                ->where('go_live_time', '<=', $asOfTime)
                ->groupBy('camp_num')
                ->orderBy('submit_time', 'desc')
                ->get();
                            
            $this->sessionTempArray["topic-child-{$topicNumber}"] = $topicChild;

            $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);
            $reviewTopic = TopicService::getReviewTopic($topicNumber);

            $topicName = (isset($topic) && isset($topic->topic_name)) ? $topic->topic_name : '';
            $reviewTopicName = (isset($reviewTopic) && isset($reviewTopic->topic_name)) ? $reviewTopic->topic_name : $topicName;
            $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $topicName);
            $topic_id = $topicNumber . "-" . $title;
            $tree = [];
            $tree[$startCamp]['topic_id'] = $topicNumber;
            $tree[$startCamp]['camp_id'] = $startCamp;
            $tree[$startCamp]['title'] = $topicName;
            $tree[$startCamp]['review_title'] = $reviewTopicName;
            $tree[$startCamp]['link'] = $rootUrl . '/' . $this->getTopicCampUrl($topicNumber, $startCamp, $asOfTime);
            $tree[$startCamp]['review_link'] = $rootUrl . '/' . $this->getTopicCampUrl($topicNumber, $startCamp, $asOfTime, true);
            $tree[$startCamp]['score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId);
            $tree[$startCamp]['children'] = $this->traverseCampTree($algorithm, $topicNumber, $startCamp, null, $asOfTime, $rootUrl);
            return $reducedTree = TopicSupport::sumTranversedArraySupportCount($tree);
        } catch (CampTreeException $th) {
            throw new CampTreeException("Prepare Camp Tree Exception");
        }
    }

    /**
     * Get the camp url.
     *
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     * @param boolean $isReview
     *
     * @return string url
     */

    public function getTopicCampUrl($topicNumber, $campNumber, $asOfTime, $isReview = false)
    {
        try {
            $urlPortion = $this->getSeoBasedUrlPortion($topicNumber, $campNumber, $asOfTime, $isReview);
            return ('topic/' . $urlPortion);
        } catch (CampURLException $th) {
            throw new CampURLException("URL Exception");
        }
    }

    /**
     * Get the seo based camp url.
     *
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     * @param boolean $isReview
     *
     * @return string url
     */
    public function getSeoBasedUrlPortion($topicNumber, $campNumber, $asOfTime, $isReview)
    {

        try {

            $topic_name = '';
            $camp_name = '';
            $topic_id_name = $topicNumber;
            $camp_num_name = $campNumber;

            $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => true]);
            $camp = $this->getLiveCamp($topicNumber, $campNumber, ['nofilter' => true], $asOfTime);

            if ($topic && isset($topic->topic_name)) {
                $topic_name = ($topic->topic_name != '') ? $topic->topic_name : $topic->title;
            }

            if ($camp && isset($camp->camp_name)) {
                $camp_name = $camp->camp_name;
            }

            // check if topic or camp are in review
            if ($isReview) {
                $ReviewTopic = TopicService::getReviewTopic($topicNumber, $asOfTime, ['nofilter' => true]);
                $ReviewCamp = $this->getReviewCamp($topicNumber, $campNumber);

                // Log::info("Log".$ReviewCamp);

                if ($ReviewTopic && isset($ReviewTopic->topic_name)) {
                    $topic_name = ($ReviewTopic->topic_name != '') ? $ReviewTopic->topic_name : $ReviewTopic->title;
                }
                if ($ReviewCamp && isset($ReviewCamp->camp_name)) {
                    $camp_name = $ReviewCamp->camp_name;
                }
            }

            if ($topic_name != '') {
                $topic_id_name = $topicNumber . "-" . preg_replace('/[^A-Za-z0-9\-]/', '-', $topic_name);
            }
            if ($camp_name != '') {
                $camp_num_name = $campNumber . "-" . preg_replace('/[^A-Za-z0-9\-]/', '-', $camp_name);
            }

            return $topic_id_name . '/' . $camp_num_name;
        } catch (CampURLException $th) {
            throw new CampURLException("URL Exception");
        }
    }

    /**
     * Get the live camp details.
     *
     * @param int $topicNumber
     * @param int $campNumber
     * @param array $filter
     * @param int $asOfTime
     *
     * @return Illuminate\Support\Collection
     */
    public function getLiveCamp($topicNumber, $campNumber, $filter = array(), $asOfTime)
    {
        try {
            $liveCamp = Camp::where('topic_num', $topicNumber)
                ->where('camp_num', '=', $campNumber)
                ->where('objector_nick_id', '=', null)
                ->where('go_live_time', '<=', $asOfTime)
                ->latest('submit_time')->first();
           
            return $liveCamp;

        } catch (CampDetailsException $th) {
            throw new CampDetailsException("Live Camp Details Exception");
        }
    }

    /**
     * Get the review camp details.
     *
     * @param int $topicNumber
     * @param int $campNumber
     *
     * @return Illuminate\Support\Collection
     */
    public function getReviewCamp($topicNumber, $campNumber)
    {

        try {
            $reviewCamp = Camp::where('topic_num', $topicNumber)
                ->where('camp_num', '=', $campNumber)
                ->where('objector_nick_id', '=', null)
                ->latest('submit_time')->first();

            return $reviewCamp;

        } catch (CampDetailsException $th) {
            throw new CampDetailsException("Review Camp Details Exception");
        }
    }

    /**
     * Get the camp support count.
     *
     * @param string $algorithm
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $supportCountTotal
     */
    public function getCamptSupportCount($algorithm, $topicNumber, $campNumber, $asOfTime, $nickNameId=null)
    {

        try {
            $supportCountTotal = 0;
            try {

                foreach ($this->sessionTempArray["topic-support-nickname-$topicNumber"] as $supported) {

                    if($nickNameId !=null && $supported->nick_name_id == $nickNameId ){
                        $nickNameSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function ($item) use ($supported) {
                            return $item->nick_name_id == $supported->nick_name_id; /* Current camp support */
                        });
                    }else{
                        $nickNameSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function ($item) use($supported) {
                            return $item->nick_name_id == $supported->nick_name_id; /* Current camp support */
                        });
                    }

                    // $supportPoint = AlgorithmService::{$algorithm}(
                    //     $supported->nick_name_id,
                    //     $supported->topic_num,
                    //     $supported->camp_num,
                    //     $asOfTime
                    // );

                    $currentCampSupport = $nickNameSupports->filter(function ($item) use ($campNumber) {
                        return $item->camp_num == $campNumber; /* Current camp support */
                    })->first();

                    /*The canonizer value should be the same as their value supporting that camp.
                    1 if they only support one party,
                    0.5 for their first, if they support 2,
                    0.25 after and half, again, for each one after that. */
                    /** Previous Logic */
                    // if ($currentCampSupport) {
                    //     $multiSupport = false; //default
                    //     if ($nickNameSupports->count() > 1) {
                    //         $multiSupport = true;
                    //         $supportCountTotal += round($supportPoint / (2 ** ($currentCampSupport->support_order)), 2);
                    //     } else if ($nickNameSupports->count() == 1) {
                    //         $supportCountTotal += $supportPoint;
                    //     }
                    //     $supportCountTotal += $this->getDeletegatedSupportCount(
                    //         $algorithm,
                    //         $topicNumber,
                    //         $campNumber,
                    //         $supported->nick_name_id,
                    //         $currentCampSupport->support_order,
                    //         $multiSupport,
                    //         $asOfTime
                    //     );
                    // }
                    /** End of previous Logic */
                    if($nickNameId && $currentCampSupport && $supported->nick_name_id == $nickNameId){

                        $supportPoint = AlgorithmService::{$algorithm}(
                            $supported->nick_name_id,
                            $supported->topic_num,
                            $supported->camp_num,
                            $asOfTime
                        );
                        $multiSupport = false; //default;

                         if ($nickNameSupports->count() > 1) {
                            $multiSupport = true;
                            $supportCountTotal += round($supportPoint / (2 ** ($currentCampSupport->support_order)), 2);
                        } else if ($nickNameSupports->count() == 1) {
                             $supportCountTotal += $supportPoint;
                        }
                        $supportCountTotal += $this->getDeletegatedSupportCount(
                            $algorithm,
                            $topicNumber,
                            $campNumber,
                            $supported->nick_name_id,
                            $currentCampSupport->support_order,
                            $multiSupport,
                            $asOfTime
                        );
                    }
                    else if ($currentCampSupport && $nickNameId == null) {
                        $supportPoint = AlgorithmService::{$algorithm}(
                            $supported->nick_name_id,
                            $supported->topic_num,
                            $supported->camp_num,
                            $asOfTime
                        );
                        $multiSupport = false; //default
                        if ($nickNameSupports->count() > 1) {
                           $multiSupport = true;
                           if($algorithm =='mind_experts'){
                               $supportCountTotal +=  $supportPoint;
                           }else{
                               $supportCountTotal +=  round($supportPoint / (2 ** ($currentCampSupport->support_order)), 2);
                           }
                       } else if ($nickNameSupports->count() == 1) {
                            $supportCountTotal += $supportPoint;
                       }
                       $supportCountTotal += $this->getDeletegatedSupportCount(
                            $algorithm,
                            $topicNumber,
                            $campNumber,
                            $supported->nick_name_id,
                            $currentCampSupport->support_order,
                            $multiSupport,
                            $asOfTime
                        );
                   }
                }
            } catch (\Exception $e) {
                echo "topic-support-nickname-$topicNumber" . $e->getMessage();
            }

            return $supportCountTotal;
        } catch (CampSupportCountException $th) {
            throw new CampSupportCountException("Camp Support Count Exception");
        }
    }

    /**
     * Get the camp support count.
     *
     * @param string $algorithm
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $delegateNickId
     * @param int $parent_support_order
     * @param boolean $multiSupport
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function getDeletegatedSupportCount($algorithm, $topicNumber, $campNumber, $delegateNickId, $parent_support_order, $multiSupport, $asOfTime)
    {

        /* Delegated Support */
        $delegatedSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function ($item) use ($delegateNickId) {
            return $item->delegate_nick_name_id == $delegateNickId;
        });

        $score = 0;
        foreach ($delegatedSupports as $support) {

            $supportPoint = AlgorithmService::{$algorithm}(
                $support->nick_name_id,
                $support->topic_num,
                $support->camp_num,
                $asOfTime
            );
            //Check for campnum
            if ($campNumber == $support['camp_num']) {
                if ($multiSupport) {
                    $score += round($supportPoint / (2 ** ($parent_support_order)), 2);
                } else {
                    $score += $supportPoint;
                }
                $score += $this->getDeletegatedSupportCount(
                    $algorithm,
                    $topicNumber,
                    $campNumber,
                    $support->nick_name_id,
                    $parent_support_order,
                    $multiSupport,
                    $asOfTime
                );
            }
        }

        return $score;
    }

    /**
     * Get the camp tree of given topic.
     *
     * @param string $algorithm
     * @param int $topicNumber
     * @param int $parentCamp
     * @param int $lastparent
     * @param int $asOfTime
     *
     * @return array $array
     */
    public function traverseCampTree($algorithm, $topicNumber, $parentCamp, $lastparent = null, $asOfTime, $rootUrl)
    {
        try {
            $key = $topicNumber . '-' . $parentCamp . '-' . $lastparent;
            if (in_array($key, $this->traversetempArray)) {
                return;
                /** Skip repeated recursions* */
            }
            $this->traversetempArray[] = $key;
            $childs = $this->campChildrens($topicNumber, $parentCamp);
            
            $array = [];
            foreach ($childs as $key => $child) {
                $oneCamp = $this->getLiveCamp($child->topic_num, $child->camp_num, ['nofilter' => true], $asOfTime);
                $reviewCamp = $this->getReviewCamp($child->topic_num, $child->camp_num);
                $reviewCampName = (isset($reviewCamp) && isset($reviewCamp->camp_name)) ? $reviewCamp->camp_name : $oneCamp->camp_name;

                $title = $oneCamp->camp_name; //preg_replace('/[^A-Za-z0-9\-]/', '-', $onecamp->camp_name);
                $topic_id = $child->topic_num . "-" . $title;

                $array[$child->camp_num]['topic_id'] = $topicNumber;
                $array[$child->camp_num]['camp_id'] = $child->camp_num;
                $array[$child->camp_num]['title'] = $title;
                $array[$child->camp_num]['review_title'] = $reviewCampName;

                $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
                $array[$child->camp_num]['link'] = $rootUrl . '/' . $this->getTopicCampUrl($child->topic_num, $child->camp_num, $asOfTime) . $queryString . '#statement';
                $array[$child->camp_num]['review_link'] = $rootUrl . '/' . $this->getTopicCampUrl($child->topic_num, $child->camp_num, $asOfTime, true) . $queryString . '#statement';
                $array[$child->camp_num]['score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime);
                $children = $this->traverseCampTree($algorithm, $child->topic_num, $child->camp_num, $child->parent_camp_num, $asOfTime, $rootUrl);

                $array[$child->camp_num]['children'] = is_array($children) ? $children : [];
            }
            return $array;
        } catch (\Exception $th) {
            abort(401, "Traverse Camp Tree with Children Exception: " . $th->getMessage());
        }
    }

    /**
     * Get the child camps.
     *
     * @param int $topicNumber
     * @param int $parentCamp
     * @param int $campNumber
     * @param array $filter
     *
     * @return array $childs
     */

    public function campChildrens($topicNum, $parentCamp, $campNum = null, $filter = array())
    {

        try {
            $childs = $this->sessionTempArray["topic-child-{$topicNum}"]->filter(function ($item) use ($parentCamp, $campNum) {
                if ($campNum) {
                    return $item->parent_camp_num == $parentCamp && $item->camp_num == $campNum;
                } else {
                    return $item->parent_camp_num == $parentCamp;
                }
            });

            return $childs;
        } catch (\Exception $th) {
            abort(401, "Get Camp Childrens Exception: " . $th->getMessage());
        }
    }

    /**
     * Get the camp tree count.
     * @param int $topicNumber
     * @param int $nickNameId
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function campTreeCount($topicNumber, $nickNameId, $asOfTime)
    {

        try {
            $camps = new Collection;

            $camps = Cache::remember("$topicNumber-bydate-support-$asOfTime", 2, function () use ($topicNumber, $asOfTime) {
                return $expertCamp = Camp::where('topic_num', '=', $topicNumber)
                    ->where('objector_nick_id', '=', null)
                    ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null group by camp_num)')
                    ->where('go_live_time', '<', $asOfTime)
                    ->orderBy('submit_time', 'desc')
                    ->groupBy('camp_num')
                    ->get();
            });

            $expertCamp = $camps->filter(function ($item) use ($nickNameId) {
                return $item->camp_about_nick_id == $nickNameId;
            })->last();

            if (!$expertCamp) { # not an expert canonized nick.
            return 0;
            }

            $key = '';
            if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == 'bydate') {
                $key = $asOfTime;
            }

            # Implemented cache for existing data.
            $supports = Cache::remember("$topicNumber-supports-$key", 2, function () use ($topicNumber, $asOfTime) {
                return Support::where('topic_num', '=', $topicNumber)
                    ->whereRaw("(start < $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->select(['support_order', 'camp_num', 'topic_num', 'nick_name_id', 'delegate_nick_name_id'])
                    ->get();
            });

            $directSupports = $supports->filter(function ($item) use ($nickNameId) {
                return $item->nick_name_id == $nickNameId && $item->delegate_nick_name_id == 0;
            });

            $delegatedSupports = $supports->filter(function ($item) use ($nickNameId) {
                return $item->nick_name_id == $nickNameId && $item->delegate_nick_name_id != 0;
            });

            # start with one person one vote canonize.

            $expertCampReducedTree = $this->prepareCampTree('blind_popularity', $topicNumber, $asOfTime, $expertCamp->camp_num, '',  $nickNameId); # only need to canonize this branch

            // Check if user supports himself
            $numOfCcampSupported = 0;

            $user_support_camps = Support::where('topic_num', '=', $topicNumber)
                ->whereRaw("(start < $asOfTime) and ((end = 0) or (end > $asOfTime))")
                ->where('nick_name_id', '=', $nickNameId)
                ->get();

            // foreach ($user_support_camps as $scamp) {
            //     $ret_camp = Camp::where('topic_num', '=', $scamp->topic_num)
            //         ->where('camp_num', '=', $scamp->camp_num)
            //         ->whereNotNull('camp_about_nick_id')
            //         ->where('camp_about_nick_id', '<>', 0)
            //         ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time < "' . $asOfTime . '" group by camp_num)')
            //         ->where('go_live_time', '<', $asOfTime)
            //         ->groupBy('camp_num')
            //         ->orderBy('submit_time', 'desc')
            //         ->get();

            //     if ($ret_camp->count()) {
            //         $num_of_camps_supported++;
            //     }
            // }

                $topicNumArr = array();
                $campNumArray = array();

                foreach ($user_support_camps as $scamp) {
                    $topicNumArr[] = $scamp->topic_num;
                    $campNumArray[] = $scamp->camp_num;
                }

            $retCamp = Camp::whereIn('topic_num', array_unique($topicNumArr))
                ->whereIn('camp_num', array_unique($campNumArray))
                ->whereNotNull('camp_about_nick_id')
                ->where('camp_about_nick_id', '<>', 0)
                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time < "' . $asOfTime . '" group by camp_num)')
                ->where('go_live_time', '<', $asOfTime)
                ->groupBy('camp_num')
                ->orderBy('submit_time', 'desc')
                ->get();

            if ($retCamp->count()) {
                $numOfCcampSupported = $retCamp->count();
            }

            if (($directSupports->count() > 0 || $delegatedSupports->count() > 0) && $numOfCcampSupported > 1) {
                return $expertCampReducedTree[$expertCamp->camp_num]['score'] * 5;
            } else {
                return $expertCampReducedTree[$expertCamp->camp_num]['score'] * 1;
            }

        } catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
    }

    /**
     * Get all agreement camps of topics.
     * @param int $pageSize
     * @param string $asof
     * @param int $asofdate
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdate, $namespaceId, $search = '', $isCount = false)
    {

        $returnTopics = [];

        try {

            if ($asof == 'default') {

                $returnTopics = DB::table('camp')->select(DB::raw('(select count(topic_support.id) from topic_support where topic_support.topic_num=camp.topic_num) as support, camp.*'))
                    ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
                    ->where('camp.go_live_time', '<=', $asofdate)
                    ->whereRaw('topic.go_live_time in (select max(topic.go_live_time) from topic where topic.topic_num=topic.topic_num and topic.objector_nick_id is null and topic.go_live_time <=' . $asofdate . ' group by topic.topic_num)');

            } else {
                if ($asof == "review") {
                    $returnTopics = DB::table('camp')->select(DB::raw('(select count(topic_support.id) from topic_support where topic_support.topic_num=camp.topic_num) as support, camp.*'))
                        ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
                        ->whereRaw('topic.go_live_time in (select max(topic.go_live_time) from topic where topic.topic_num=topic.topic_num and topic.objector_nick_id is null group by topic.topic_num)');

                } else if ($asof == "bydate") {

                    //$asofdate = strtotime(date('Y-m-d H:i:s', strtotime($asofdate)));
                    $returnTopics = DB::table('camp')->select(DB::raw('(select count(topic_support.id) from topic_support where topic_support.topic_num=camp.topic_num) as support, camp.*'))
                        ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
                        ->where('camp.go_live_time', '<=', $asofdate);
                }
            }
        } catch (\Throwable $th) {
            throw new AgreementCampsException("Exception in GetAgreementCamp:". $th->getMessage());
        }

        /* Common conditions in all queries */
        $returnTopics
            ->where('camp_name', '=', 'Agreement')
            ->where('topic.objector_nick_id', '=', null);

        $returnTopics->when($namespaceId !== '', function ($q) use($namespaceId) {     
            $q->whereIn('namespace_id', explode(',', $namespaceId));
        });

            /* if the search paramet is set then add search condition in the query */
        if (isset($search) && $search != '') {
             $returnTopics->where('title', 'like', '%' . $search . '%');
         };

        $returnTopics
            ->latest('support')
            ->groupBy('topic.topic_num')
            ->orderBy('topic.topic_name', 'DESC');

        if($isCount){
            return $returnTopics->get()->count();
         }

        return $returnTopics
            ->skip($skip)
            ->take($pageSize)
            ->get();
    }

     /**
     * Get the camp count .
     * @param int $nickNameId
     * @param string $condition
     * @param boolean $political
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function campCount($nickNameId, $condition, $political=false, $topicNumber=0, $campNumber=0, $asOfTime = null){
        // $as_of_time = time();
        $cacheWithTime = false;
        // if(isset($_REQUEST['asof']) && $_REQUEST['asof'] == 'bydate'){
        //     if(isset($_REQUEST['asofdate']) && !empty($_REQUEST['asofdate'])){
        //         $as_of_time = strtotime($_REQUEST['asofdate']);
        //         $cacheWithTime = true;
        //     }
        // }

        $sql = "select count(*) as countTotal,support_order,camp_num from support where nick_name_id = $nickNameId and (" .$condition.")";
        $sql2 ="and ((start < $asOfTime) and ((end = 0) or (end > $asOfTime)))";

        /* Cache applied to avoid repeated queries in recursion */
        if($cacheWithTime){
            $result = Cache::remember("$sql $sql2", 2, function () use($sql,$sql2) {
                return DB::select("$sql $sql2");
            });
            return isset($result[0]->countTotal) ? $result[0]->countTotal : 0;
        }else{
            $result = Cache::remember("$sql", 1, function () use($sql,$sql2) {
                return DB::select("$sql $sql2");
            });

		 if($political==true && $topicNumber==231 && ($campNumber==2 ||  $campNumber==3 || $campNumber==4) ) {

			if($result[0]->support_order==1)
				$total = $result[0]->countTotal / 2;
			else if($result[0]->support_order==2)
				$total = $result[0]->countTotal / 4;
			else if($result[0]->support_order==3)
				$total = $result[0]->countTotal / 6;
			else if($result[0]->support_order==4)
				$total = $result[0]->countTotal / 8;
			else $total = $result[0]->countTotal;

		 } else {
			$total = $result[0]->countTotal;
		 }


            return isset($result[0]->countTotal) ? $total : 0;
        }
    }
}
