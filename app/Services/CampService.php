<?php

namespace App\Services;

use Illuminate\Support\Arr;
use App\Model\v1\TopicSupport;
use App\Model\v1\Support;
use App\Model\v1\Camp;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use AlgorithmService;
use App\Exceptions\Camp\CampTreeException;
use App\Exceptions\Camp\CampURLException;
use App\Exceptions\Camp\CampDetailsException;
use App\Exceptions\Camp\CampSupportCountException;
use App\Exceptions\Camp\CampTreeCountException;
use TopicService;
use Illuminate\Support\Facades\Log;

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


    public function prepareCampTree($algorithm, $topicNumber, $asOfTime, $startCamp = 1)
    {
        try {

            $this->traversetempArray = [];

            if (!Arr::exists($this->sessionTempArray, "topic-support-nickname-{$topicNumber}")) {

                $nickNameSupport =  Support::where('topic_num', '=', $topicNumber)
                    ->where('delegate_nick_name_id', 0)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->groupBy('nick_name_id')
                    ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
                    ->get();

                $this->sessionTempArray["topic-support-nickname-{$topicNumber}"] = $nickNameSupport;
            }

            if (!Arr::exists($this->sessionTempArray, "topic-support-{$topicNumber}")) {

                $topicSupport =   Support::where('topic_num', '=', $topicNumber)
                    ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start', 'DESC')
                    ->select(['support_order', 'camp_num', 'nick_name_id', 'delegate_nick_name_id', 'topic_num'])
                    ->get();

                $this->sessionTempArray["topic-support-{$topicNumber}"] = $topicSupport;
            }

            $topicChild =  Camp::where('topic_num', '=', $topicNumber)
                ->where('camp_name', '!=', 'Agreement')
                ->where('objector_nick_id', '=', NULL)
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
            $tree[$startCamp]['link'] = $this->getTopicCampUrl($topicNumber, $startCamp, $asOfTime);
            $tree[$startCamp]['review_link'] = $this->getTopicCampUrl($topicNumber, $startCamp, $asOfTime, true);
            $tree[$startCamp]['score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime);
            $tree[$startCamp]['children'] = $this->traverseCampTree($algorithm, $topicNumber, $startCamp, null, $asOfTime);
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
            return url('topic/' . $urlPortion);
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

            $topic = TopicService::getLiveTopic($topicNumber, $asOfTime,  ['nofilter' => true]);
            $camp = $this->getLiveCamp($topicNumber, $campNumber, ['nofilter' => true], $asOfTime);

            if ($topic && isset($topic->topic_name)) {
                $topic_name = ($topic->topic_name != '') ? $topic->topic_name : $topic->title;
            }

            if ($camp && isset($camp->camp_name)) {
                $camp_name = $camp->camp_name;
            }

            // check if topic or camp are in review
            if ($isReview) {
                $ReviewTopic = TopicService::getReviewTopic($topicNumber, $asOfTime,  ['nofilter' => true]);
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
            if (isset($filter['nofilter']) && $filter['nofilter']) {
                $asOfTime  = time();
            }

            return Camp::where('topic_num', $topicNumber)
                ->where('camp_num', '=', $campNumber)
                ->where('objector_nick_id', '=', NULL)
                ->where('go_live_time', '<=', $asOfTime)
                ->latest('submit_time')->first();
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
            return Camp::where('topic_num', $topicNumber)
                ->where('camp_num', '=', $campNumber)
                ->where('objector_nick_id', '=', NULL)
                ->where('grace_period', '=', 0)
                ->latest('submit_time')->first();
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
    public function getCamptSupportCount($algorithm, $topicNumber, $campNumber, $asOfTime)
    {

        try {
            $supportCountTotal = 0;
            try {

                foreach ($this->sessionTempArray["topic-support-nickname-$topicNumber"] as $supported) {
                    $nickNameSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function ($item) use ($supported) {
                        return $item->nick_name_id == $supported->nick_name_id; /* Current camp support */
                    });
                    $supportPoint = AlgorithmService::{$algorithm}(
                        $supported->nick_name_id,
                        $supported->topic_num,
                        $supported->camp_num,
                        $asOfTime
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
    public function traverseCampTree($algorithm, $topicNumber, $parentCamp, $lastparent = null, $asOfTime)
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
                //$childCount  = count($child->children($child->topic_num,$child->camp_num));
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
                $array[$child->camp_num]['link'] = $this->getTopicCampUrl($child->topic_num, $child->camp_num, $asOfTime) . $queryString . '#statement';
                $array[$child->camp_num]['review_link'] = $this->getTopicCampUrl($child->topic_num, $child->camp_num, $asOfTime, true) . $queryString . '#statement';
                $array[$child->camp_num]['score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime);
                $children = $this->traverseCampTree($algorithm, $child->topic_num, $child->camp_num, $child->parent_camp_num, $asOfTime);

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
                    ->where('objector_nick_id', '=', NULL)
                    ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null group by camp_num)')
                    ->where('go_live_time', '<', $asOfTime)
                    ->orderBy('submit_time', 'desc')
                    ->groupBy('camp_num')
                    ->get();
            });

            $expertCamp = $camps->filter(function ($item) use ($nickNameId) {
                return  $item->camp_about_nick_id == $nickNameId;
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
                return  $item->nick_name_id == $nickNameId && $item->delegate_nick_name_id == 0;
            });

            $delegatedSupports = $supports->filter(function ($item) use ($nickNameId) {
                return $item->nick_name_id == $nickNameId && $item->delegate_nick_name_id != 0;
            });

            # start with one person one vote canonize.

            $expertCampReducedTree = $this->prepareCampTree('blind_popularity', $topicNumber, $asOfTime,  $expertCamp->camp_num); # only need to canonize this branch

            // Check if user supports himself
            $num_of_camps_supported = 0;

            $user_support_camps = Support::where('topic_num', '=', $topicNumber)
                ->whereRaw("(start < $asOfTime) and ((end = 0) or (end > $asOfTime))")
                ->where('nick_name_id', '=', $nickNameId)
                ->get();

            foreach ($user_support_camps as $scamp) {
                $ret_camp = Camp::where('topic_num', '=', $scamp->topic_num)
                    ->where('camp_num', '=', $scamp->camp_num)
                    ->whereNotNull('camp_about_nick_id')
                    ->where('camp_about_nick_id', '<>', 0)
                    ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time < "' . $asOfTime . '" group by camp_num)')
                    ->where('go_live_time', '<', $asOfTime)
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
        } catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
    }
}
