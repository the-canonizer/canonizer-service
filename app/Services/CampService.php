<?php

namespace App\Services;

use Illuminate\Support\Arr;
use App\Model\v1\TopicSupport;
use App\Model\v1\Support;
use App\Model\v1\Camp;
use App\Model\v1\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use AlgorithmService;

/**
 * Class CampService.
 *
 */
class CampService
{

    // private static $tempArray = [];
    // private static $childtempArray = [];  // uncomment when mid expert will shift
    // private static $chilcampArray = [];
    // private static $campChildren = [];
    // private static $totalSupports = [];
    // private static $totalNickNameSupports = [];
    private static $traversetempArray = [];
    private static $sessionTempArray = [];

    const AGREEMENT_CAMP = "Agreement";

    /**
     * prepare Camp tree based on algorithm.
     *
     * @param string $algorithm
     * @param int $topicNumber
     * @param int $startCamp
     *
     * @return array $tree
     */


    public function prepareCampTree(
        $algorithm,
        $topicNumber,
        $startCamp = 1
    ) {

        $as_of_time = time();
        self::$traversetempArray = [];

        if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == 'bydate')) {
            $as_of_time = strtotime($_REQUEST['asofdate']);
        }

        if (!Arr::exists(self::$sessionTempArray, "topic-support-nickname-{$topicNumber}")) {

            $nickNameSupport =  Support::where('topic_num', '=', $topicNumber)
                ->where('delegate_nick_name_id', 0)
                ->whereRaw("(start <= $as_of_time) and ((end = 0) or (end > $as_of_time))")
                ->orderBy('start', 'DESC')
                ->groupBy('nick_name_id')
                ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
                ->get();

            self::$sessionTempArray["topic-support-nickname-{$topicNumber}"] = $nickNameSupport;
        }

        if (!Arr::exists(self::$sessionTempArray, "topic-support-{$topicNumber}")) {

            $topicSupport =   Support::where('topic_num', '=', $topicNumber)
                ->whereRaw("(start <= $as_of_time) and ((end = 0) or (end > $as_of_time))")
                ->orderBy('start', 'DESC')
                ->select(['support_order', 'camp_num', 'nick_name_id', 'delegate_nick_name_id', 'topic_num'])
                ->get();

            self::$sessionTempArray["topic-support-{$topicNumber}"] = $topicSupport;
        }


        if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "default")) {

            $topicChild =  Camp::where('topic_num', '=', $topicNumber)
                ->where('camp_name', '!=', 'Agreement')
                ->where('objector_nick_id', '=', NULL)
                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time < "' . time() . '" group by camp_num)')
                ->where('go_live_time', '<=', time())
                ->groupBy('camp_num')
                ->orderBy('submit_time', 'desc')
                ->get();

            self::$sessionTempArray["topic-child-{$topicNumber}"] = $topicChild;
        } else {

            if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "review")) {

                $topicChild =  Camp::where('topic_num', '=', $topicNumber)
                    ->where('camp_name', '!=', 'Agreement')
                    ->where('objector_nick_id', '=', NULL)
                    ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num =' . $topicNumber . ' and objector_nick_id is null group by camp_num)')
                    ->orderBy('submit_time', 'desc')
                    ->groupBy('camp_num')
                    ->get();
                self::$sessionTempArray["topic-child-{$topicNumber}"] = $topicChild;
            } else if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate")) {

                if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate") {
                    $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                }

                $topicChild =   Camp::where('topic_num', '=', $topicNumber)
                    ->where('camp_name', '!=', 'Agreement')
                    ->where('objector_nick_id', '=', NULL)
                    ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time <= ' . $asofdate . ' group by camp_num)')
                    ->orderBy('submit_time', 'DESC')
                    ->groupBy('camp_num')
                    ->get();
                self::$sessionTempArray["topic-child-{$topicNumber}"] = $topicChild;
            }
        }

        // return self::$sessionTempArray;
        $topic = Topic::getLiveTopic($topicNumber, ['nofilter' => false]);

        $topic_name = (isset($topic) && isset($topic->topic_name)) ? $topic->topic_name : '';
        $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $topic_name);
        $topic_id = $topicNumber . "-" . $title;
        $tree = [];
        $tree[$startCamp]['title'] = $topic_name;
        //  url('topic/' . $topic_id . '/' . $this->camp_num.'#statement');
        $tree[$startCamp]['link'] = $this->getTopicCampUrl($topicNumber, $startCamp);
        $tree[$startCamp]['score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp);
        $tree[$startCamp]['children'] = $this->traverseCampTree($algorithm, $topicNumber, $startCamp);


        return $reducedTree = TopicSupport::sumTranversedArraySupportCount($tree);
    }

    /**
     * Get the camp url.
     * @param string $algorithm
     * @param int $topicNumber
     * @param int $campNumber
     *
     * @return string url
     */

    public function getTopicCampUrl($topicNumber, $campNumber)
    {
        $urlPortion = $this->getSeoBasedUrlPortion($topicNumber, $campNumber);
        return url('topic/' . $urlPortion);
    }

    /**
     * Get the seo based camp url.
     *
     * @param int $topicNumber
     * @param int $campNumber
     *
     * @return string url
     */
    public function getSeoBasedUrlPortion($topicNumber, $campNumber)
    {
        $topic = \App\Model\v1\Topic::getLiveTopic($topicNumber, ['nofilter' => true]);
        $camp = $this->getLiveCamp($topicNumber, $campNumber, ['nofilter' => true]);
        $topic_name = '';
        $camp_name = '';
        if ($topic && isset($topic->topic_name)) {
            $topic_name = ($topic->topic_name != '') ? $topic->topic_name : $topic->title;
        }
        if ($camp && isset($camp->camp_name)) {
            $camp_name = $camp->camp_name;
        }
        $topic_id_name = $topicNumber;
        $camp_num_name = $campNumber;
        if ($topic_name != '') {
            $topic_id_name = $topicNumber . "-" . preg_replace('/[^A-Za-z0-9\-]/', '-', $topic_name);
        }
        if ($camp_name != '') {
            $camp_num_name = $campNumber . "-" . preg_replace('/[^A-Za-z0-9\-]/', '-', $camp->camp_name);
        }

        return $topic_id_name . '/' . $camp_num_name;
    }

    /**
     * Get the live camp details.
     *
     * @param int $topicNumber
     * @param int $campNumber
     * @param array $filter
     *
     * @return string object
     */
    public function getLiveCamp($topicNumber, $campNumber, $filter = array())
    {
        if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "default")) {

            return Camp::where('topic_num', $topicNumber)
                ->where('camp_num', '=', $campNumber)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', time())
                ->latest('submit_time')->first();
        } else {

            if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "review")) {

                return Camp::where('topic_num', $topicNumber)
                    ->where('camp_num', '=', $campNumber)
                    ->where('objector_nick_id', '=', NULL)
                    ->latest('submit_time')->first();
            } else if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate")) {
                if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate") {
                    $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                }
                if (isset($filter['nofilter']) && $filter['nofilter']) {
                    $asofdate  = time();
                }

                return Camp::where('topic_num', $topicNumber)
                    ->where('camp_num', '=', $campNumber)
                    ->where('objector_nick_id', '=', NULL)
                    ->where('go_live_time', '<=', $asofdate)
                    ->latest('submit_time')->first();
            }
        }
    }

    /**
     * Get the camp support count.
     *
     * @param string $algorithm
     * @param int $topicNumber
     * @param int $campNumber
     *
     * @return int $supportCountTotal
     */
    public function getCamptSupportCount($algorithm, $topicNumber, $campNumber)
    {

        $supportCountTotal = 0;
        try {

            foreach (self::$sessionTempArray["topic-support-nickname-$topicNumber"] as $supported) {
                $nickNameSupports = self::$sessionTempArray["topic-support-{$topicNumber}"]->filter(function ($item) use ($supported) {
                    return $item->nick_name_id == $supported->nick_name_id; /* Current camp support */
                });
                   $supportPoint = AlgorithmService::{$algorithm}(
                    $supported->nick_name_id,
                    $supported->topic_num,
                    $supported->camp_num
                );

                $currentCampSupport = $nickNameSupports->filter(function ($item) use ($campNumber) {
                    return $item->camp_num == $campNumber; /* Current camp support */
                })->first();


                /*The canonizer value should be the same as their value supporting that camp.
				   1 if they only support one party,
				   0.5 for their first, if they support 2,
				   0.25 after and half, again, for each one after that. */
                if ($currentCampSupport) {
                    $multiSupport = false; //default
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
                        $multiSupport
                    );
                }
            }
        } catch (\Exception $e) {
            echo "topic-support-nickname-$topicNumber" . $e->getMessage();
        }

        return $supportCountTotal;
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
     *
     * @return int $score
     */
    public function getDeletegatedSupportCount(
        $algorithm,
        $topicNumber,
        $campNumber,
        $delegateNickId,
        $parent_support_order,
        $multiSupport
    ) {

        /* Delegated Support */
        $delegatedSupports = self::$sessionTempArray["topic-support-{$topicNumber}"]->filter(function ($item) use ($delegateNickId) {
            return $item->delegate_nick_name_id == $delegateNickId;
        });

        $score = 0;
        foreach ($delegatedSupports as $support) {

            $supportPoint = AlgorithmService::{$algorithm}($support->nick_name_id, $support->topic_num, $support->camp_num);
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
                    $multiSupport
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
     *
     * @return array $array
     */
    public function traverseCampTree(
        $algorithm,
        $topicNumber,
        $parentCamp,
        $lastparent = null
    ) {
        $key = $topicNumber . '-' . $parentCamp . '-' . $lastparent;
        if (in_array($key, self::$traversetempArray)) {
            return;
            /** Skip repeated recursions* */
        }
        self::$traversetempArray[] = $key;
        $childs = $this->campChildrens($topicNumber, $parentCamp);
        $array = [];
        foreach ($childs as $key => $child) {
            //$childCount  = count($child->children($child->topic_num,$child->camp_num));
            $oneCamp = $this->getLiveCamp($child->topic_num, $child->camp_num, ['nofilter' => true]);
            $title = $oneCamp->camp_name; //preg_replace('/[^A-Za-z0-9\-]/', '-', $onecamp->camp_name);
            $topic_id = $child->topic_num . "-" . $title;
            $array[$child->camp_num]['title'] = $title;
            $queryString = (app('request')->getQueryString()) ? '?' . app('request')->getQueryString() : "";
            $array[$child->camp_num]['link'] = $this->getTopicCampUrl($child->topic_num, $child->camp_num) . $queryString . '#statement';
            $array[$child->camp_num]['score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num);
            $children = $this->traverseCampTree($algorithm, $child->topic_num, $child->camp_num, $child->parent_camp_num);

            $array[$child->camp_num]['children'] = is_array($children) ? $children : [];
        }
        return $array;
    }

    /**
     * Get the child camps.
     *
     * @param int $topicNumber
     * @param int $parentCamp
     * @param int $campNumber
     *
     * @return array $childs
     */

    public function campChildrens(
        $topicNum,
        $parentCamp,
        $campNum = null,
        $filter = array()
    ) {

        $childs = self::$sessionTempArray["topic-child-{$topicNum}"]->filter(function ($item) use ($parentCamp, $campNum) {
            if ($campNum) {
                return $item->parent_camp_num == $parentCamp && $item->camp_num == $campNum;
            } else {
                return $item->parent_camp_num == $parentCamp;
            }
        });

        return $childs;
    }

    /**
     * Get the child camps.
     *
     * @param int $topicNumber
     * @param array $filter
     *
     * @return array camp
     */
    public function getAgreementTopic($topicNumber, $filter = array())
    {
        if (isset($filter['asof']) && $filter['asof'] == "default") {
            return Camp::select('topic.topic_name', 'topic.namespace_id', 'camp.*', 'namespace.name as namespace_name', 'namespace.name')
                ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
                ->join('namespace', 'topic.namespace_id', '=', 'namespace.id')
                ->where('topic.topic_num', $topicNumber)->where('camp_name', '=', 'Agreement')
                ->where('camp.objector_nick_id', '=', NULL)
                ->where('topic.objector_nick_id', '=', NULL)
                ->where('camp.go_live_time', '<=', time())
                ->where('topic.go_live_time', '<=', time())
                ->latest('topic.submit_time')->first();
        } else {

            if ((isset($filter['asof']) && $filter['asof'] == "review")) {
                return Camp::select('topic.topic_name', 'topic.namespace_id', 'camp.*', 'namespace.name as namespace_name', 'namespace.name')
                    ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
                    ->join('namespace', 'topic.namespace_id', '=', 'namespace.id')
                    ->where('camp.topic_num', $topicNumber)->where('camp_name', '=', 'Agreement')
                    ->where('camp.objector_nick_id', '=', NULL)
                    ->where('topic.objector_nick_id', '=', NULL)
                    ->latest('topic.submit_time')->first();
            } else if (isset($filter['asof']) && $filter['asof'] == "bydate") {

                if (isset($filter['asof']) && $filter['asof'] == "bydate") {
                    $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($filter['asofdate'])));
                }
                if (isset($filter['nofilter']) && $filter['nofilter']) {
                    $asofdate  = time();
                }
                return Camp::select('topic.topic_name', 'topic.namespace_id', 'camp.*', 'namespace.name as namespace_name', 'namespace.name')
                    ->join('topic', 'topic.topic_num', '=', 'camp.topic_num')
                    ->join('namespace', 'topic.namespace_id', '=', 'namespace.id')
                    ->where('camp.topic_num', $topicNumber)->where('camp_name', '=', 'Agreement')
                    ->where('camp.objector_nick_id', '=', NULL)
                    ->where('topic.objector_nick_id', '=', NULL)
                    ->where('topic.go_live_time', '<=', $asofdate)
                    ->latest('topic.go_live_time')->first();
            }
        }
    }


     /**
     * Get the camp tree count.
     * @param int $topicNumber
     * @param int $nickNameId
     *
     * @return int $score
     */
    public function campTreeCount($topicNumber, $nickNameId)
    {

        $camps = new Collection;
        if (!isset($_REQUEST['asof']) || (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "default")) {

            $camps = Cache::remember("$topicNumber-default-support", 2, function () use ($topicNumber) {
                return Camp::where('topic_num', '=', $topicNumber)
                    ->where('objector_nick_id', '=', NULL)
                    ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time < "' . time() . '" group by camp_num)')
                    ->where('go_live_time', '<', time())
                    ->groupBy('camp_num')
                    ->orderBy('submit_time', 'desc')
                    ->get();
            });
        } else {

            if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "review") {
                $camps = Cache::remember("$topicNumber-review-support", 2, function () use ($topicNumber) {
                    return Camp::where('topic_num', '=', $topicNumber)
                        ->where('objector_nick_id', '=', NULL)
                        ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null group by camp_num)')
                        ->orderBy('submit_time', 'desc')
                        ->groupBy('camp_num')
                        ->get();
                });
            } else if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate") {

                $asofdate =  strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                $camps = Cache::remember("$topicNumber-bydate-support-$asofdate", 2, function () use ($topicNumber, $asofdate) {
                    return $expertCamp = Camp::where('topic_num', '=', $topicNumber)
                        ->where('objector_nick_id', '=', NULL)
                        ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null group by camp_num)')
                        ->where('go_live_time', '<', $asofdate)
                        ->orderBy('submit_time', 'desc')
                        ->groupBy('camp_num')
                        ->get();
                });
            }
        }

         $expertCamp = $camps->filter(function ($item) use ($nickNameId) {
            return  $item->camp_about_nick_id == $nickNameId;
        })->last();

        if (!$expertCamp) { # not an expert canonized nick.
            return 0;
        }

        $as_of_time = time();
        $key = '';
        if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == 'bydate') {
            $as_of_time = strtotime($_REQUEST['asofdate']);
            $key = $as_of_time;
        }

        # Implemented cache for existing data.
        $supports = Cache::remember("$topicNumber-supports-$key", 2, function () use ($topicNumber, $as_of_time) {
            return Support::where('topic_num', '=', $topicNumber)
                ->whereRaw("(start < $as_of_time) and ((end = 0) or (end > $as_of_time))")
                ->orderBy('start', 'DESC')
                ->select(['support_order', 'camp_num', 'topic_num', 'nick_name_id', 'delegate_nick_name_id'])
                ->get();
        });

        $directSupports = $supports->filter(function ($item) use ($nickNameId) {
            return  $item->nick_name_id == $nickNameId && $item->delegate_nick_name_id == 0;
        });

        $delegatedSupports = $supports->filter(function ($item) use ($nickNameId) {
            return $item->nick_name_id == $nickNameId && $item->delegate_nick_name_id != 0;
        });

        # start with one person one vote canonize.

        $expertCampReducedTree = $this->prepareCampTree('blind_popularity', $topicNumber, $expertCamp->camp_num); # only need to canonize this branch

        // Check if user supports himself
        $num_of_camps_supported = 0;

        $user_support_camps = Support::where('topic_num', '=', $topicNumber)
            ->whereRaw("(start < $as_of_time) and ((end = 0) or (end > $as_of_time))")
            ->where('nick_name_id', '=', $nickNameId)
            ->get();

        foreach ($user_support_camps as $scamp) {
            $ret_camp = Camp::where('topic_num', '=', $scamp->topic_num)
                ->where('camp_num', '=', $scamp->camp_num)
                ->whereNotNull('camp_about_nick_id')
                ->where('camp_about_nick_id', '<>', 0)
                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time < "' . time() . '" group by camp_num)')
                ->where('go_live_time', '<', time())
                ->groupBy('camp_num')
                ->orderBy('submit_time', 'desc')
                ->get();

            if ($ret_camp->count()) {
                $num_of_camps_supported++;
            }
        }

        if (($directSupports->count() > 0 || $delegatedSupports->count() > 0) && $num_of_camps_supported > 1) {
            return $expertCampReducedTree[$expertCamp->camp_num]['score'] * 5;
        } else {
            return $expertCampReducedTree[$expertCamp->camp_num]['score'] * 1;
        }
    }
}
