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
use App\Model\v1\CampSubscription;
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

    public function prepareCampTree($algorithm, $topicNumber, $asOfTime, $startCamp = 1, $rootUrl = '', $nickNameId = null, $asOf = 'default', $fetchTopicHistory = 0)
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

            if($asOf == 'review') {
                $topicChild = Camp::where('topic_num', '=', $topicNumber)
                                ->where('camp_name', '!=', 'Agreement')
                                ->where('objector_nick_id', '=', null)
                                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null group by camp_num)')
                                ->groupBy('camp_num')
                                ->orderBy('submit_time', 'desc')
                                ->get();

            } else {
                $topicChild = Camp::where('topic_num', '=', $topicNumber)
                                ->where('camp_name', '!=', 'Agreement')
                                ->where('objector_nick_id', '=', null)
                                ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time <= ' . $asOfTime . ' group by camp_num)')
                                ->where('go_live_time', '<=', $asOfTime)
                                ->groupBy('camp_num')
                                ->orderBy('submit_time', 'desc')
                                ->get();
            }
            
            $this->sessionTempArray["topic-child-{$topicNumber}"] = $topicChild;
            $topic = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false], $asOf, $fetchTopicHistory);
            $reviewTopic = TopicService::getReviewTopic($topicNumber);
            
            $topicName = (isset($topic) && isset($topic->topic_name)) ? $topic->topic_name : '';
            $reviewTopicName = (isset($reviewTopic) && isset($reviewTopic->topic_name)) ? $reviewTopic->topic_name : $topicName;
            $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $topicName);
            $topic_id = $topicNumber . "-" . $title;
            $agreementCamp = $this->getLiveCamp($topicNumber, 1, ['nofilter' => true], $asOfTime, $asOf);
            $isDisabled = 0;
            $isOneLevel = 0;
            if (!empty($agreementCamp)) {
                $isDisabled = $agreementCamp->is_disabled ?? 0;
                $isOneLevel = $agreementCamp->is_one_level ?? 0;
            }
            $tree = [];
            $tree[$startCamp]['topic_id'] = $topicNumber;
            $tree[$startCamp]['camp_id'] = $startCamp;
            $tree[$startCamp]['title'] = $topicName;
            $tree[$startCamp]['review_title'] = $reviewTopicName;
            $tree[$startCamp]['link'] = $rootUrl . '/' . $this->getTopicCampUrl($topicNumber, $startCamp, $asOfTime);
            $tree[$startCamp]['review_link'] = $rootUrl . '/' . $this->getTopicCampUrl($topicNumber, $startCamp, $asOfTime, true);
            $tree[$startCamp]['score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId);
            $tree[$startCamp]['full_score'] = $this->getCamptSupportCount($algorithm, $topicNumber, $startCamp, $asOfTime, $nickNameId,true);
            $tree[$startCamp]['submitter_nick_id'] = $topic->submitter_nick_id ?? '';
            
            $topicCreatedDate = TopicService::getTopicCreatedDate($topicNumber);
            $tree[$startCamp]['created_date'] = $topicCreatedDate ?? 0;
            $tree[$startCamp]['is_valid_as_of_time'] = $asOfTime >= $topicCreatedDate ? true : false;
            $tree[$startCamp]['is_disabled'] = $isDisabled;
            $tree[$startCamp]['is_one_level'] = $isOneLevel;
            $tree[$startCamp]['subscribed_users'] = $this->getTopicCampSubscriptions($topicNumber, $startCamp);
            $tree[$startCamp]['children'] = $this->traverseCampTree($algorithm, $topicNumber, $startCamp, null, $asOfTime, $rootUrl, $asOf, $tree);
            return $reducedTree = TopicSupport::sumTranversedArraySupportCount($tree);
        } catch (CampTreeException $th) {
            throw new CampTreeException("Prepare Camp Tree Exception");
        }
    }

    /**
     * Get the topic/camp subscriptions.
     *
     * @param int $topicNumber
     * @param int $campNumber
     *
     * @return array subscribedBy
     */

    public function getTopicCampSubscriptions($topicNumber, $campNumber) {
        try {
            $campSubscriptionsArr = [];
            $campSubscriptions = CampSubscription::where([['topic_num','=',$topicNumber],
                ['camp_num','=',$campNumber]])->whereNull('subscription_end')->pluck('user_id')->toArray();
            if (count($campSubscriptions) > 0) {
                $explicitArr = array("explicit" => true);
                $campSubscriptionsArr = array_fill_keys($campSubscriptions, $explicitArr);
            } 
            return $campSubscriptionsArr;
        } catch (CampURLException $th) {
            abort(401, "Topic Camp Subscribe Exception: " . $th->getMessage());
        }
    }

    /**
     * Change the subscription array in case of implicit to parent.
     *
     * @param array $childCampSubscribers
     * @param boolean $explicity
     * @param string $camp-title
     * @return int campNumber
     */
    public function changeArrayExplicity($childCampSubscribers, $title, $campNumber, $explicity= false) {
        $newArr = [];
        foreach ($childCampSubscribers as $key => $value) {
            $newValue = $value;
            $newValue['explicit'] = $explicity;
            if (!$explicity) {
                $newValue['child_camp_name'] = $title;
                $newValue['child_camp_id'] = $campNumber;
            }
            $newArr[$key] = $newValue; // Can be true/false.
        }
        return $newArr;
    }

    /**
     * Get the camp created date .
     *
     * @param Illuminate\Database\Eloquent\Collection
     *
     * @return Illuminate\Database\Eloquent\Collection;
     */

    public function getCampCreatedDate($campNumber, $topicNumber){
        return Camp::where('topic_num', $topicNumber)->where('camp_num', $campNumber)
                ->pluck('submit_time')
                ->first();
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
    public function getLiveCamp($topicNumber, $campNumber, $filter = array(), $asOfTime, $asOf = 'default')
    {
        try {
            $query = Camp::where('topic_num', $topicNumber)
                ->where('camp_num', '=', $campNumber);
                if($asOf == 'default' || $asOf == 'review') {
                    $query->where('objector_nick_id', NULL);
                }
                if($asOf == 'default') {
                    $query->where('go_live_time', '<=', time());
                }
                if($asOf == 'bydate') {
                    $query->where('go_live_time', '<=', $asOfTime);
                }
            
            $liveCamp = $query->orderBy('go_live_time', 'desc')->first(); // ticket 1219 Muhammad Ahmad
           
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
                ->where('grace_period', 0)
                ->where('objector_nick_id', '=', null)
                ->orderBy('go_live_time', 'desc')->first(); // ticket 1219 Muhammad Ahmad

            return $reviewCamp;

        } catch (CampDetailsException $th) {
            throw new CampDetailsException("Review Camp Details Exception");
        }
    }

    /**
     * Get the camp support count.
     *
     * @param string $algorithm
     * @param int $topicnum
     * @param int $campnum
     * @param int $asOfTime
     * @param int $nick_name_id
     * @return int $supportCountTotal
     */
    public function getCamptSupportCount($algorithm, $topicnum, $campnum,$asOfTime,$nick_name_id=null, $full_score = false) {
        try{
            if(!Arr::exists($this->sessionTempArray, "score_tree_{$topicnum}_{$algorithm}")){
                $score_tree = $this->getCampAndNickNameWiseSupportTree($algorithm, $topicnum,$asOfTime);
                $this->sessionTempArray["score_tree_{$topicnum}_{$algorithm}"] = $score_tree;
            }else{
                $score_tree = $this->sessionTempArray["score_tree_{$topicnum}_{$algorithm}"];
            } 
            $support_total = 0;
            try{
                if(array_key_exists('camp_wise_tree',$score_tree) && count($score_tree['camp_wise_tree']) > 0 && array_key_exists($campnum,$score_tree['camp_wise_tree'])){
                    if(count($score_tree['camp_wise_tree'][$campnum]) > 0){
                        foreach($score_tree['camp_wise_tree'][$campnum] as $order=>$tree_node){                                        
                            if(count($tree_node) > 0){
                                foreach($tree_node as $nick=>$score){
                                   $delegate_arr = $score_tree['nick_name_wise_tree'][$nick][$order][$campnum];
                                   $delegate_score = $this->getDelegatesScore($delegate_arr,$full_score);
                                   if($full_score){
                                    $delegate_full_score = $this->getDelegatesFullScore($delegate_arr);
                                    $support_total = $support_total + $score['full_score'] + $delegate_full_score; 
                                   }else{
                                     $support_total =$support_total + $score['score'] + $delegate_score;
                                   }
                                   
                                }
                            }
                        }    
                    }
                }         
               return $support_total;
            }catch (\Exception $e) {
                return $e->getMessage();
            }
        }catch (CampSupportCountException $th) {
            throw new CampSupportCountException("Camp Support Count Exception");
        }
    }
    // public function getCamptSupportCount($algorithm, $topicNumber, $campNumber, $asOfTime, $nickNameId=null)
    // {

    //     try {
    //         $supportCountTotal = 0;
    //         try {

    //             foreach ($this->sessionTempArray["topic-support-nickname-$topicNumber"] as $supported) {

    //                 if($nickNameId !=null && $supported->nick_name_id == $nickNameId ){
    //                     $nickNameSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function ($item) use ($supported) {
    //                         return $item->nick_name_id == $supported->nick_name_id; /* Current camp support */
    //                     });
    //                 }else{
    //                     $nickNameSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function ($item) use($supported) {
    //                         return $item->nick_name_id == $supported->nick_name_id; /* Current camp support */
    //                     });
    //                 }

    //                 // $supportPoint = AlgorithmService::{$algorithm}(
    //                 //     $supported->nick_name_id,
    //                 //     $supported->topic_num,
    //                 //     $supported->camp_num,
    //                 //     $asOfTime
    //                 // );

    //                 $currentCampSupport = $nickNameSupports->filter(function ($item) use ($campNumber) {
    //                     return $item->camp_num == $campNumber; /* Current camp support */
    //                 })->first();

    //                 /*The canonizer value should be the same as their value supporting that camp.
    //                 1 if they only support one party,
    //                 0.5 for their first, if they support 2,
    //                 0.25 after and half, again, for each one after that. */
    //                 /** Previous Logic */
    //                 // if ($currentCampSupport) {
    //                 //     $multiSupport = false; //default
    //                 //     if ($nickNameSupports->count() > 1) {
    //                 //         $multiSupport = true;
    //                 //         $supportCountTotal += round($supportPoint / (2 ** ($currentCampSupport->support_order)), 2);
    //                 //     } else if ($nickNameSupports->count() == 1) {
    //                 //         $supportCountTotal += $supportPoint;
    //                 //     }
    //                 //     $supportCountTotal += $this->getDeletegatedSupportCount(
    //                 //         $algorithm,
    //                 //         $topicNumber,
    //                 //         $campNumber,
    //                 //         $supported->nick_name_id,
    //                 //         $currentCampSupport->support_order,
    //                 //         $multiSupport,
    //                 //         $asOfTime
    //                 //     );
    //                 // }
    //                 /** End of previous Logic */
    //                 if($nickNameId && $currentCampSupport && $supported->nick_name_id == $nickNameId){

    //                     $supportPoint = AlgorithmService::{$algorithm}(
    //                         $supported->nick_name_id,
    //                         $supported->topic_num,
    //                         $supported->camp_num,
    //                         $asOfTime
    //                     );
    //                     $multiSupport = false; //default;

    //                     if ($nickNameSupports->count() > 1) {
    //                         $multiSupport = true;
    //                         $supportCountTotal += round($supportPoint / (2 ** ($currentCampSupport->support_order)), 2);
    //                     } else if ($nickNameSupports->count() == 1) {
    //                         $supportCountTotal += $supportPoint;
    //                     }
    //                     $supportCountTotal += $this->getDeletegatedSupportCount(
    //                         $algorithm,
    //                         $topicNumber,
    //                         $campNumber,
    //                         $supported->nick_name_id,
    //                         $currentCampSupport->support_order,
    //                         $multiSupport,
    //                         $asOfTime
    //                     );
    //                 }
    //                  else if ($currentCampSupport && $nickNameId == null) {                        
    //                     $supportPoint = AlgorithmService::{$algorithm}(
    //                         $supported->nick_name_id,
    //                         $supported->topic_num,
    //                         $supported->camp_num,
    //                         $asOfTime
    //                     );
    //                     $multiSupport = false; //default
    //                     if ($nickNameSupports->count() > 1) {
    //                         $multiSupport = true;
    //                         if($algorithm =='mind_experts'){
    //                             $supportCountTotal +=  $supportPoint;
    //                         }else{
    //                             $supportCountTotal +=  round($supportPoint / (2 ** ($currentCampSupport->support_order)), 2);
    //                         }
    //                     } else if ($nickNameSupports->count() == 1) {
    //                         $supportCountTotal += $supportPoint;
    //                     }
    //                     $supportCountTotal += $this->getDeletegatedSupportCount(   
    //                         $algorithm,
    //                         $topicNumber,
    //                         $campNumber,
    //                         $supported->nick_name_id,
    //                         $currentCampSupport->support_order,
    //                         $multiSupport,
    //                         $asOfTime
    //                     );
    //                 }
    //             }
    //         } catch (\Exception $e) {
    //             return $e->getMessage();
    //         }

    //         return $supportCountTotal;
    //     } catch (CampSupportCountException $th) {
    //         throw new CampSupportCountException("Camp Support Count Exception");
    //     }
    // }

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
                    $score += round($supportPoint / (2 ** ($parent_support_order)), 3);
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
    public function traverseCampTree($algorithm, $topicNumber, $parentCamp, $lastparent = null, $asOfTime, $rootUrl, $asOf = 'default', & $lastArray)
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
                $oneCamp = $this->getLiveCamp($child->topic_num, $child->camp_num, ['nofilter' => true], $asOfTime, $asOf);
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
                $array[$child->camp_num]['full_score'] = $this->getCamptSupportCount($algorithm, $child->topic_num, $child->camp_num, $asOfTime,null,true);
                $array[$child->camp_num]['submitter_nick_id'] = $child->submitter_nick_id ?? '';
                $array[$child->camp_num]['created_date'] = $oneCamp->submit_time ?? 0;
                $array[$child->camp_num]['is_disabled'] = $child->is_disabled ?? 0;
                $array[$child->camp_num]['is_one_level'] = $child->is_one_level ?? 0;
                $array[$child->camp_num]['subscribed_users'] = $this->getTopicCampSubscriptions($child->topic_num, $child->camp_num); 

                if($child->parent_camp_num == 1) {
                    $parentCamp = TopicService::getLiveTopic($topicNumber, $asOfTime, ['nofilter' => false]);
                } else {
                    $parentCamp = $this->getLiveCamp($child->topic_num, $child->parent_camp_num, ['nofilter' => true], $asOfTime, $asOf);
                }
                
                // Set the implicit subscription of the parent camp.
                $implicitParentSubscriptionArray = $this->changeArrayExplicity($array[$child->camp_num]['subscribed_users'], $title, $child->camp_num);
                $lastArray[$child->parent_camp_num]['subscribed_users'] = $lastArray[$child->parent_camp_num]['subscribed_users'] + $implicitParentSubscriptionArray;
                
                $array[$child->camp_num]['parent_camp_is_disabled'] = $parentCamp->is_disabled ?? 0;
                $array[$child->camp_num]['parent_camp_is_one_level'] = $parentCamp->is_one_level ?? 0;
                
                $children = $this->traverseCampTree($algorithm, $child->topic_num, $child->camp_num, $child->parent_camp_num, $asOfTime, $rootUrl, $asOf, $array);

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
     * @param int $topicNum
     * @param int $campNum
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function campTreeCount($topicNumber, $nickNameId,$topicNum,$campNum, $asOfTime)
    {

        try {
            $expertCamp = self::getExpertCamp($topicNumber,$nickNameId,$asOfTime);
            if(!$expertCamp){ # not an expert canonized nick.
                return 0;
            }
            $score_multiplier = self::getMindExpertScoreMultiplier($expertCamp,$topicNumber,$nickNameId,$asOfTime);
        
        
		# start with one person one vote canonize.
       
         if($topicNum == 81 || $topicNum == 124){  // mind expert special case
            $algo = 'blind_popularity';
            if(!Arr::exists($this->sessionTempArray, "score_tree_{$topicNumber}_{$algo}")){
                $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree($algo, $topicNumber,$asOfTime);
                $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"] = $expertCampReducedTree;
            }else{
                $expertCampReducedTree = $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"];
            } 
            //$expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree('blind_popularity',$topicNumber,$asOfTime); # only need to canonize this branch
            $total_score = 0;
            if(array_key_exists('camp_wise_tree',$expertCampReducedTree) && array_key_exists($expertCamp->camp_num,$expertCampReducedTree['camp_wise_tree']) && count($expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num]) > 0){
                foreach($expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num] as $tree_node){
                    if(count($tree_node) > 0){
                        foreach($tree_node as $score){
                            $total_score = $total_score + $score['score'];
                        }
                    }                
                }
            }  
            
            return $total_score * $score_multiplier;
        }else{
           $expertCampReducedTree = self::mindExpertsNonSpecial($topicNumber,$nickNameId,$asOfTime,$topicNum); # only need to canonize this branch
            $total_score = 0;
            if(count($expertCampReducedTree) > 0){
                foreach($expertCampReducedTree as $tree_node){
                    if(count($tree_node) > 0){
                        foreach($tree_node as $score){
                            $total_score = $total_score + $score['score'];
                        }
                    }                
                }
            }
            return $total_score;
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
    public function getAllAgreementTopicCamps($pageSize, $skip, $asof, $asofdate, $namespaceId, $nickNameIds, $search = '', $isCount = false)
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

        /* Common conditions in all queries */
        $returnTopics
            ->where('camp_name', '=', 'Agreement')
            ->where('topic.objector_nick_id', '=', null);

        $returnTopics->when($namespaceId !== '', function ($q) use($namespaceId) {
            $q->where('namespace_id', $namespaceId);
        });

        $returnTopics->when(!empty($nickNameIds), function ($q) use($nickNameIds) { 
            $q->whereIn('camp.submitter_nick_id', $nickNameIds);
        });

        /* if the search paramet is set then add search condition in the query */
        if (isset($search) && $search != '') {

            $returnTopics->where('topic.topic_name', 'like', '%' . $search . '%');

            if($asof == "bydate") {
                $returnTopics->where('topic.go_live_time', '<=', $asofdate);
            }
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
        } catch (\Throwable $th) {
            throw new AgreementCampsException("Exception in GetAgreementCamp:". $th->getMessage());
        }
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
    public function campCount($nickNameId, $condition, $political=false, $topicNumber=0, $campNumber=0, $asOfTime = null,$topic_num = 0){
        // $as_of_time = time();
        $cacheWithTime = false;
        $total = 0;
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
        }

            if($political==true && $topicNumber==231 && ($campNumber==2 ||  $campNumber==3 || $campNumber==4 || $campNumber==6) ) {
                $sqlQuery = "select count(*) as countTotal,support_order,camp_num from support where nick_name_id = $nickNameId and topic_num = ".$topicNumber." and ((start < $asOfTime) and ((end = 0) or (end > $asOfTime)))";	
                $supportCount = DB::select("$sqlQuery");
                if($supportCount[0]->countTotal > 1 && $topic_num!=231){
                    if($result[0]->support_order == 1){
                        for($i=1; $i<=$supportCount[0]->countTotal; $i++){
                            $supportPoint = $result[0]->countTotal;
                            if($i == 1 || $i == $supportCount[0]->countTotal){ // adding only last reminder
                                $total = $total + round($supportPoint * 1 / (2 ** ($i)), 3);
                            }
                        }
                    }else{
                        $supportPoint = $result[0]->countTotal;
                        $total = $total + round($supportPoint * 1 / (2 ** ($result[0]->support_order)), 3);
                    }
                }else{
                    $total = $result[0]->countTotal;
                } 
                // if($result[0]->support_order==1)
                //     $total = $result[0]->countTotal / 2;
                // else if($result[0]->support_order==2)
                //     $total = $result[0]->countTotal / 4;
                // else if($result[0]->support_order==3)
                //     $total = $result[0]->countTotal / 6;
                // else if($result[0]->support_order==4)
                //     $total = $result[0]->countTotal / 8;
                // else $total = $result[0]->countTotal;

            } else {
                $total = $result[0]->countTotal;
            }


            return $total;
    }

     /**
     * Get the camp tree count.
     * @param int $topicNumber
     * @param int $nickNameId
     * @param int $asOfTime
     *
     * @return $expertCamp
     */

    public static function getExpertCamp($topicnum,$nick_name_id,$asOfTime){
        try{
            $camps = new Collection;
            $camps = Cache::remember("$topicnum-bydate-support-$asOfTime", 2, function () use ($topicnum, $asOfTime) {
                    return $expertCamp = Camp::where('topic_num', '=', $topicnum)
                        ->where('objector_nick_id', '=', null)
                        ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicnum . ' and objector_nick_id is null group by camp_num)')
                        ->where('go_live_time', '<', $asOfTime)
                        ->orderBy('submit_time', 'desc')
                        ->groupBy('camp_num')
                        ->get();
                });

            $expertCamp = $camps->filter(function($item) use($nick_name_id){
                return  $item->camp_about_nick_id == $nick_name_id;
            })->last();
            return $expertCamp;
        } catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
    }

     /**
     * Get the camp tree count.
     * @param int $topicNumber
     * @param int $nickNameId
     * @param int $asOfTime
     *
     * @return int $camp_wise_score_tree
     */

    public function mindExpertsNonSpecial($topicNumber,$nickNameId,$asOfTime,$topicNum){
        try {
        $expertCamp = self::getExpertCamp($topicNumber,$nickNameId,$asOfTime);
        if(!$expertCamp){ # not an expert canonized nick.
            return 0;
        }

        $score_multiplier = self::getMindExpertScoreMultiplier($expertCamp,$topicNumber,$nickNameId,$asOfTime);
        if($topicNum == 124){
            $algo = 'computer_science_experts';
            if(!Arr::exists($this->sessionTempArray, "score_tree_{$topicNumber}_{$algo}")){
                $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree($algo, $topicNumber,$asOfTime);
                $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"] = $expertCampReducedTree;
            }else{
                $expertCampReducedTree = $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"];
            } 
           // $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree('computer_science_experts',$topicNumber,$asOfTime); # only need to canonize this branch
        }else{
           /// $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree('mind_experts',$topicNumber,$asOfTime); # only need to canonize this branch
            $algo = 'mind_experts';
            if(!Arr::exists($this->sessionTempArray, "score_tree_{$topicNumber}_{$algo}")){
                $expertCampReducedTree = $this->getCampAndNickNameWiseSupportTree($algo, $topicNumber,$asOfTime);
                $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"] = $expertCampReducedTree;
            }else{
                $expertCampReducedTree = $this->sessionTempArray["score_tree_{$topicNumber}_{$algo}"];
            } 
        }
             // Check if user supports himself
        if(array_key_exists('camp_wise_tree',$expertCampReducedTree) && array_key_exists($expertCamp->camp_num,$expertCampReducedTree['camp_wise_tree'])){
            return $expertCampReducedTree['camp_wise_tree'][$expertCamp->camp_num];

        }else{
            return [];
        }
    } catch (CampTreeCountException $th) {
        throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
    }
        
    }

     /**
     * Get the camp tree count.
     * @param $expertCamp
     * @param int $topicNumber
     * @param int $nickNameId
     * @param int $asOfTime
     * @return int $score_multiplier
     */
    public static function getMindExpertScoreMultiplier($expertCamp,$topicNumber=0,$nickNameId=0,$asOfTime){
        try{
            $key = '';
		if(isset($_REQUEST['asof']) && $_REQUEST['asof']=='bydate'){
            $key = $asOfTime;
		}
        
		# Implemented cache for existing data. 
        $supports = Cache::remember("$topicNumber-supports-$key", 2, function () use($topicNumber,$asOfTime) {
                 return Support::where('topic_num','=',$topicNumber)
                    ->whereRaw("(start < $asOfTime) and ((end = 0) or (end > $asOfTime))")
                    ->orderBy('start','DESC')
                    ->select(['support_order','camp_num','topic_num','nick_name_id','delegate_nick_name_id'])
                    ->get();
        });

        $num_of_camps_supported = 0;
        $user_support_camps = Support::where('topic_num','=',$topicNumber)
            ->whereRaw("(start < $asOfTime) and ((end = 0) or (end > $asOfTime))")
            ->where('nick_name_id', '=', $nickNameId)
            ->get();
        $topic_num_array = array();
        $camp_num_array = array();
    
        foreach ($user_support_camps as $scamp) {
            $topic_num_array[] = $scamp->topic_num;
            $camp_num_array[] = $scamp->camp_num;
        }

        $is_supporting_own_expert = 0;
        if(in_array($expertCamp->camp_num,$camp_num_array) && in_array($expertCamp->topic_num,$topic_num_array)){
            $is_supporting_own_expert = 1;
        }
              
        $ret_camp = Camp::whereIn('topic_num', array_unique($topic_num_array))
            ->whereIn('camp_num', array_unique($camp_num_array))
            ->whereNotNull('camp_about_nick_id')
            ->where('camp_about_nick_id', '<>', 0)
            ->whereRaw('go_live_time in (select max(go_live_time) from camp where topic_num=' . $topicNumber . ' and objector_nick_id is null and go_live_time < "' . $asOfTime . '" group by camp_num)')
            ->where('go_live_time', '<', $asOfTime)
            ->groupBy('camp_num')
            ->orderBy('submit_time', 'desc')
            ->get();
        if ($ret_camp->count()) {
            $num_of_camps_supported = $ret_camp->count();
        }
        $score_multiplier = 1;
        if(!$is_supporting_own_expert || $num_of_camps_supported > 1) {
            $score_multiplier = 5; 
         }
        return $score_multiplier;
        } catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
        
    }

    

    public function delegateSupportTree($algorithm, $topicNumber, $campnum, $delegateNickId, $parent_support_order, $parent_score,$multiSupport,$array=[],$asOfTime){
        try{
            $nick_name_support_tree=[];
            $nick_name_wise_support=[];
            $nick_name_delegate_support_tree = [];
            $is_add_reminder_back_flag = 1;// ($algorithm == 'blind_popularity') ? 1 : 0;
		/* Delegated Support */
        if (!Arr::exists($this->sessionTempArray, "topic-support-{$topicNumber}")){
            $supportData = Support::where('topic_num', '=', $topicNumber)
            ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
            ->orderBy('start', 'DESC')
            ->select(['support_order', 'camp_num', 'nick_name_id', 'delegate_nick_name_id', 'topic_num'])
            ->get();
            $this->sessionTempArray["topic-support-{$topicNumber}"] = $supportData;
            $delegatedSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function($item) use ($delegateNickId) {
                return $item->delegate_nick_name_id == $delegateNickId;
            });
        }else{
            $delegatedSupports = $this->sessionTempArray["topic-support-{$topicNumber}"]->filter(function($item) use ($delegateNickId) {
                return $item->delegate_nick_name_id == $delegateNickId;
            });
        }

        
        
        if(count($delegatedSupports) > 0){
           foreach($delegatedSupports as $support){
                    if(array_key_exists($support->nick_name_id, $nick_name_wise_support)){
                            array_push($nick_name_wise_support[$support->nick_name_id],$support);
                    }else{
                        $nick_name_wise_support[$support->nick_name_id] = [];
                        array_push($nick_name_wise_support[$support->nick_name_id],$support);
                    }              
           }
        }
        
        foreach($nick_name_wise_support as $nickNameId=>$support_camp){
            foreach($support_camp as $support){ 
                $supportPoint = AlgorithmService::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num,$asOfTime);
                $support_total = 0; 
                $full_support_total = 0; 
                     if($multiSupport){
                         $support_total = $support_total + round($supportPoint * 1 / (2 ** ($support->support_order)), 3);
                     }else{
                         $support_total = $support_total + $supportPoint;
                     }
                     $full_support_total = $full_support_total +  $supportPoint;
                     $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['score']  = $support_total;
                     $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['full_score']  = $full_support_total;
                }
         }
        
         if(count($nick_name_support_tree) > 0){
             foreach($nick_name_support_tree as $nickNameId=>$scoreData){
                 ksort($scoreData);
                 $index = 0;
                 $multiSupport =  count($scoreData) > 1 ? 1 : 0;
                 foreach($scoreData as $support_order=>$camp_score){
                     $index = $index +1;
                    foreach($camp_score as $campNum=>$score){
                         if($support_order > 1 && $index == count($scoreData)  && $is_add_reminder_back_flag){
                             if(array_key_exists($nickNameId,$nick_name_support_tree) && array_key_exists(1,$nick_name_support_tree[$nickNameId]) && count(array_keys($nick_name_support_tree[$nickNameId][1])) > 0){
                             $campNumber = array_keys($nick_name_support_tree[$nickNameId][1])[0];
                             $nick_name_support_tree[$nickNameId][1][$campNumber]['score']=$nick_name_support_tree[$nickNameId][1][$campNumber]['score'] + $score['score'];
                             $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber,$campNumber, $nickNameId, $parent_support_order,$parent_score,$multiSupport ,[],$asOfTime);
                             $nick_name_support_tree[$nickNameId][1][$campNumber]['delegates'] = $delegateTree;
                         }
                     }
                     $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber,$campNum, $nickNameId, $parent_support_order, $parent_score,$multiSupport,[],$asOfTime);
                     $nick_name_support_tree[$nickNameId][$support_order][$campNum]['delegates'] = $delegateTree;
                    }
                 }
             }
         }
         if(count($nick_name_support_tree) > 0){
             foreach($nick_name_support_tree as $nick=>$data){
                 foreach($data as $support_order=>$camp_score){
                    foreach($camp_score as $campNum=>$score){
                         if($campNum == $campnum){
                             $nick_name_delegate_support_tree[$nick]['score'] =   $score['score'];
                             $nick_name_delegate_support_tree[$nick]['full_score'] =   $score['full_score'];
                             $nick_name_delegate_support_tree[$nick]['delegates'] = $score['delegates'];
                         }
                    }
                 }
                
             }
         }         
        return $nick_name_delegate_support_tree;

       }catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
    }


    

    public function getCampAndNickNameWiseSupportTree($algorithm, $topicNumber,$asOfTime){
        try{

            $is_add_reminder_back_flag = 1;//($algorithm == 'blind_popularity') ? 1 : 0;
            $nick_name_support_tree=[];
            $nick_name_wise_support=[];
            $camp_wise_support = [];
            $camp_wise_score = [];
            $topic_support = Support::where('topic_num', '=', $topicNumber)
            ->where('delegate_nick_name_id', 0)
            ->whereRaw("(start <= $asOfTime) and ((end = 0) or (end > $asOfTime))")
            ->orderBy('camp_num','ASC')->orderBy('support_order','ASC')
            ->select(['nick_name_id', 'delegate_nick_name_id', 'support_order', 'topic_num', 'camp_num'])
            ->get();
            
            if(count($topic_support) > 0){
               foreach($topic_support as $support){
                        if(array_key_exists($support->nick_name_id, $nick_name_wise_support)){
                                array_push($nick_name_wise_support[$support->nick_name_id],$support);
                        }else{
                            $nick_name_wise_support[$support->nick_name_id] = [];
                            array_push($nick_name_wise_support[$support->nick_name_id],$support);
                        }                   
               }
            }
            foreach($nick_name_wise_support as $nickNameId=>$support_camp){
                $multiSupport =  count($support_camp) > 1 ? 1 : 0;
               foreach($support_camp as $support){                
                    $support_total = 0; 
                    $full_support_total = 0;
                    $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['score'] = 0;
                    $camp_wise_score[$support->camp_num][$support->support_order][$support->nick_name_id]['score'] = 0;
                    $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['full_score'] = 0;
                        $camp_wise_score[$support->camp_num][$support->support_order][$support->nick_name_id]['full_score'] = 0;
                    $supportPoint = AlgorithmService::{$algorithm}($support->nick_name_id,$support->topic_num,$support->camp_num,$asOfTime);
                    if($multiSupport){
                            $support_total = $support_total + round($supportPoint * 1 / (2 ** ($support->support_order)), 3);
                        }else{
                            $support_total = $support_total + $supportPoint;
                        } 
                        $full_support_total = $full_support_total +  $supportPoint;                 
                        $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['score'] = $support_total;
                        $camp_wise_score[$support->camp_num][$support->support_order][$support->nick_name_id]['score'] =  $support_total;
                        $nick_name_support_tree[$support->nick_name_id][$support->support_order][$support->camp_num]['full_score'] = $full_support_total;
                        $camp_wise_score[$support->camp_num][$support->support_order][$support->nick_name_id]['full_score'] =  $full_support_total;
                                      
               }
            }
            if(count($nick_name_support_tree) > 0){
                foreach($nick_name_support_tree as $nickNameId=>$scoreData){
                    ksort($scoreData);
                    $index = 0;
                    $multiSupport =  count($scoreData) > 1 ? 1 : 0;
                    foreach($scoreData as $support_order=>$camp_score){
                        $index = $index +1;                        
                       foreach($camp_score as $campNum=>$score){
                            if($support_order > 1 && $index == count($scoreData)  && $is_add_reminder_back_flag){
                                if(array_key_exists($nickNameId,$nick_name_support_tree) && array_key_exists(1,$nick_name_support_tree[$nickNameId]) && count(array_keys($nick_name_support_tree[$nickNameId][1])) > 0){
                                $campNumber = array_keys($nick_name_support_tree[$nickNameId][1])[0];
                                $nick_name_support_tree[$nickNameId][1][$campNumber]['score']=$nick_name_support_tree[$nickNameId][1][$campNumber]['score'] + $score['score'];
                                $camp_wise_score[$campNumber][1][$nickNameId]['score'] = $camp_wise_score[$campNumber][1][$nickNameId]['score'] + $score['score'];
                                $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber,$campNumber, $nickNameId, $support_order,$camp_wise_score[$campNumber][1][$nickNameId]['score'],$multiSupport ,[],$asOfTime);
                                $nick_name_support_tree[$nickNameId][1][$campNumber]['delegates'] = $delegateTree;
                            }
                        }
                        $delegateTree = $this->delegateSupportTree($algorithm, $topicNumber,$campNum, $nickNameId, $support_order, $nick_name_support_tree[$nickNameId][$support_order][$campNum]['score'],$multiSupport,[],$asOfTime);
                        $nick_name_support_tree[$nickNameId][$support_order][$campNum]['delegates'] = $delegateTree;
                       }
                    }
                }
            }
        
            return ['camp_wise_tree'=>$camp_wise_score,'nick_name_wise_tree'=>$nick_name_support_tree];

        }catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
    }

    public function getDelegatesFullScore($tree){
        try{
            $score = 0;
            if(count($tree['delegates']) > 0){
                foreach($tree['delegates'] as $nick=>$delScore){
                    $score = $score + $delScore['full_score'];              
                    if(count($delScore['delegates']) > 0){
                        $score = $score + $this->getDelegatesFullScore($delScore);
                    }
                }
            }
            return $score;
            }catch (CampTreeCountException $th) {
                throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
            }
    }
    
    public function getDelegatesScore($tree,$full_score = false){
        try{
        $score = 0;
        if(count($tree['delegates']) > 0){
            foreach($tree['delegates'] as $nick=>$delScore){
                $score = $score + $delScore['score'];
                if($full_score){
                    $score = $score + $delScore['full_score'];
                }                
                if(count($delScore['delegates']) > 0){
                    $score = $score + $this->getDelegatesScore($delScore,$full_score);
                }
            }
        }
        return $score;
        }catch (CampTreeCountException $th) {
            throw new CampTreeCountException("Camp Tree Count with Mind Expert Algorithm Exception");
        }
        
    }
}
