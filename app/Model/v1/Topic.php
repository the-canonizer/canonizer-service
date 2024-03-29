<?php

namespace App\Model\v1;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Model\v1\Camp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
class Topic extends Model {

    protected $table = 'topic';
    public $timestamps = false;

    public static function boot() {
        static::created(function ($model) {
            // while creating topic for very first time
            // this will not run when updating
            if ($model->topic_num == '' || $model->topic_num == null) {
                $nextTopicNum = DB::table('topic')->max('topic_num');
                $nextTopicNum++;
                $model->topic_num = $nextTopicNum;
                $model->update();

                //create agreement
                $camp = new Camp();
                $camp->topic_num = $model->topic_num;
                $camp->parent_camp_num = null;
                $camp->key_words ='';
                $camp->language= $model->language;
                $camp->note = $model->note;
                $camp->submit_time = time();
                $camp->submitter_nick_id = $model->submitter_nick_id;
                $camp->go_live_time = $model->go_live_time;
                $camp->title = $model->topic_name;
                $camp->camp_name = Camp::AGREEMENT_CAMP;

                $camp->save();

            }
        });
        parent::boot();
    }

    public function camps() {
        return $this->hasMany('App\Model\Camp', 'topic_num', 'topic_num');
    }

    public function camps1() {
        return $this->hasMany('App\Model\Camp', 'topic_num', 'topic_num')->groupBy('camp_num');
    }
	public function topic() {
        return $this->hasOne('App\Model\v1\Topic', 'topic_num', 'topic_num')->orderBy('go_live_time','DESC');
    }
	public function supports() {
        return $this->hasMany('App\Model\Support', 'topic_num', 'topic_num')->orderBy('support_order','ASC');
    }
	public function objectornickname() {
        return $this->hasOne('App\Model\Nickname', 'id', 'objector_nick_id');
    }
	public function submitternickname() {
        return $this->hasOne('App\Model\Nickname', 'id', 'submitter_nick_id');
    }
	public function topicnamespace() {
        return $this->hasOne('App\Model\Namespaces', 'id', 'namespace_id');
    }

	public function scopeGetsupports($query,$topic_num,$userNickname=null,$filter=array()) {
		$as_of_time=time()+100;
        if(isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate"){
                    $as_of_time = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                }else if(session()->has('asofdateDefault') && session('asofdateDefault') && !isset($_REQUEST['asof'])){
                    $as_of_time = strtotime(session('asofdateDefault'));
                }
         if(isset($filter['nofilter']) && $filter['nofilter']){
                    $as_of_time  = time();
                }
		return $supports = Support::where('topic_num',$topic_num)
							//->where('delegate_nick_name_id',0)
							->whereIn('nick_name_id',$userNickname)
							->whereRaw("(start < $as_of_time) and ((end = 0) or (end > $as_of_time))")
							->orderBy('support_order','ASC')
                            ->groupBy('camp_num')
							->get();

	}

	public static function getHistory($topicnum,$filter=array()) {

		return self::where('topic_num',$topicnum)->latest('submit_time')->get();
	}

    public static function getLiveTopic($topicnum, $filter = array()) {
        if ((!isset($_REQUEST['asof']) && !Session::has('asofDefault')) || (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "default")  || (Session::has('asofDefault') && Session::get('asofDefault') == 'default' && !isset($_REQUEST['asof']))) {

            return self::where('topic_num', $topicnum)
                            ->where('objector_nick_id', '=', NULL)
                            ->where('go_live_time', '<=', time())
                            ->latest('submit_time')->first();
        } else {

            if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "review") || (Session::get('asofDefault')=="review" && !isset($_REQUEST['asof']))) {

                return self::where('topic_num', $topicnum)
                                ->where('objector_nick_id', '=', NULL)
                                ->latest('submit_time')->first();
            } else if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate")  || (Session::has('asofDefault') && Session::get('asofDefault') == 'bydate' && !isset($_REQUEST['asof']))) {
                if(isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate"){
                    $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                }else if(Session::has('asofdateDefault') && Session::get('asofdateDefault') && !isset($_REQUEST['asof'])){
                    $asofdate = strtotime(Session::get('asofdateDefault'));
                }
                if(isset($filter['nofilter']) && $filter['nofilter']){
                    $asofdate  = time();
                }

                return self::where('topic_num', $topicnum)
                                ->where('objector_nick_id', '=', NULL)
                                ->where('go_live_time', '<=', $asofdate)
                                ->latest('submit_time')->first();
            }
        }
    }

    // public static function getLiveTopic($topicnum, $filter = array()) {
    //     if ((!isset($_REQUEST['asof']) && !Session::has('asofDefault')) || (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "default")  || (Session::has('asofDefault') && Session::get('asofDefault') == 'default' && !isset($_REQUEST['asof']))) {

    //         return self::where('topic_num', $topicnum)
    //                         ->where('objector_nick_id', '=', NULL)
    //                         ->where('go_live_time', '<=', time())
    //                         ->latest('submit_time')->first();
    //     } else {

    //         if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "review") || (Session::get('asofDefault')=="review" && !isset($_REQUEST['asof']))) {

    //             return self::where('topic_num', $topicnum)
    //                             ->where('objector_nick_id', '=', NULL)
    //                             ->latest('submit_time')->first();
    //         } else if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate")  || (Session::has('asofDefault') && Session::get('asofDefault') == 'bydate' && !isset($_REQUEST['asof']))) {
    //             if(isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate"){
    //                 $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
    //             }else if(Session::has('asofdateDefault') && Session::get('asofdateDefault') && !isset($_REQUEST['asof'])){
    //                 $asofdate = strtotime(Session::get('asofdateDefault'));
    //             }
    //             if(isset($filter['nofilter']) && $filter['nofilter']){
    //                 $asofdate  = time();
    //             }

    //             return self::where('topic_num', $topicnum)
    //                             ->where('objector_nick_id', '=', NULL)
    //                             ->where('go_live_time', '<=', $asofdate)
    //                             ->latest('submit_time')->first();
    //         }
    //     }
    // }


    public static function checkDateNotEql($d1, $d2) {
        $date1 = Carbon::createFromTimestamp($d1);
        $date2 = Carbon::createFromTimestamp($d2);

        return $result = $date1->ne($date2);
        //ne() function returns true if $date1 is not equal to $date2.
    }

}
