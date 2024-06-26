<?php

namespace App\Model\v1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Statement extends Model {

    protected $table = 'statement';
    public $timestamps = false;
    protected static $tempArray = [];

    const AGREEMENT_CAMP = "Agreement";

    // public static function boot() { // currently this boot is un-used, and occuring issue for creating instance.

    // }

    public function objectornickname() {
        return $this->hasOne('App\Model\Nickname', 'id', 'objector_nick_id');
    }

    public function submitternickname() {
        return $this->hasOne('App\Model\Nickname', 'id', 'submitter_nick_id');
    }

    public static function getHistory($topicnum, $campnum, $filter = array()) {

        return self::where('topic_num', $topicnum)->where('camp_num', $campnum)->latest('submit_time')->get();
    }

    public static function getCampStatements($topicnum, $campnum){
       $statements = self ::where('topic_num', $topicnum)
                            ->where('camp_num', $campnum)
                            ->where('objector_nick_id', '=', NULL)
                            ->orderBy('submit_time', 'desc')
                            ->first();
        return count($statements) ? 1 : 0 ;
    }
    public static function getLiveStatement($topicnum, $campnum, $filter = array()) {

        if ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == "default")) {

            return self::where('topic_num', $topicnum)
                            ->where('camp_num', $campnum)
                            ->where('objector_nick_id', '=', NULL)
                            ->where('go_live_time', '<=', time())
                            ->orderBy('submit_time', 'desc')
                            ->first();
        } else {

            if (session('asofDefault')=="review") {

                return self::where('topic_num', $topicnum)
                                ->where('camp_num', $campnum)
                                ->where('objector_nick_id', '=', NULL)
                                ->orderBy('submit_time', 'desc')
                                ->first();
            }

			else if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "review") {

                return self::where('topic_num', $topicnum)
                                ->where('camp_num', $campnum)
                                ->where('objector_nick_id', '=', NULL)
                                ->orderBy('submit_time', 'desc')
                                ->first();
            } else if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == "bydate") {

                $asofdate = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                return self::where('topic_num', $topicnum)
                                ->where('camp_num', $campnum)
                                ->where('objector_nick_id', '=', NULL)
                                ->where('go_live_time', '<=', $asofdate)
                                ->orderBy('submit_time', 'desc')
                                ->first();
            } else {

				return self::where('topic_num', $topicnum)
                            ->where('camp_num', $campnum)
                            ->where('objector_nick_id', '=', NULL)
                            ->where('go_live_time', '<=', time())
                            ->orderBy('submit_time', 'desc')
                            ->first();
			}
        }
    }
	public static function getAnyStatement($topicnum, $campnum, $filter = array()) {


            return self::where('topic_num', $topicnum)
                            ->where('camp_num', $campnum)->get();

    }


    public static function getLiveStatementText($topicnum, $campnum)
    {
        $statement = self::select('parsed_value')
            ->where('topic_num', $topicnum)
            ->where('camp_num', $campnum)
            ->where('objector_nick_id', '=', null)
            ->where('go_live_time', '<=', time())
            ->orderBy('submit_time', 'desc')
            ->first()->parsed_value ?? null;

        $statement = preg_replace('/[^a-zA-Z0-9_ %\.\?%&-]/s', '', self::stripTagsExcept($statement, ['figure', 'table']));
        $statement = Str::of($statement)->trim()->words(30);
        return $statement;
    }


    public static function stripTagsExcept($html, $excludeTags = [])
    {
        if (!is_string($html)) {
            return $html;
        }
        $excludeTagsPattern = implode('|', array_map(function ($tag) {
            return preg_quote($tag, '/');
        }, $excludeTags));

        // Remove the content and tags of the excluded tags
        $pattern = '/<(' . $excludeTagsPattern . ')\b[^>]*>(.*?)<\/\1>/is';
        $html = preg_replace($pattern, '', $html);

        // Strip all remaining tags
        return strip_tags($html);

        // Decode HTML entities to get the proper text
        // $cleanedText = html_entity_decode($cleanedText, ENT_QUOTES, 'UTF-8');
    }

}
