<?php

namespace App\Models\v2;

use App\Models\v1\Camp;
use App\Models\v1\Topic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Nickname extends Model
{
    protected $table = 'nick_name';

    public $timestamps = false;

    public function camps()
    {
        return $this->hasMany('App\Model\Camp', 'nick_name_id', 'nick_name_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supports()
    {
        return $this->hasMany('App\Model\Support', 'nick_name_id', 'nick_name_id')->orderBy('support_order', 'ASC');
    }

    public static function personNickname()
    {
        if (Auth::check()) {
            return DB::table('nick_name')->select('id', 'nick_name')->where('user_id', Auth::user()->id)->orderBy('nick_name', 'ASC')->get();
        }

        return [];
    }

    public static function personNicknameArray($nickId = '')
    {

        $userNickname = [];
        if (isset($nickId) && ! empty($nickId)) {
            $nicknames = self::personAllNicknamesByAnyNickId($nickId);
        } else {
            $nicknames = self::personNickname();
        }

        foreach ($nicknames as $nickname) {
            $userNickname[] = $nickname->id;
        }

        return $userNickname;
    }

    public static function getNickNameLink($userId, $namespaceId, $topicNum = '', $campNum = '')
    {
        return config('global.APP_URL_FRONT_END') . ('/user/supports/'.$userId .'?topicnum='.$topicNum . '&campnum='.$campNum .'&namespace='.$namespaceId);
    }

    public function getSupportCampList($namespace = 1, $filter = [])
    {

        $as_of_time = time();
        $as_of_clause = '';

        $namespace = isset($_REQUEST['namespace']) ? $_REQUEST['namespace'] : $namespace;

        if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == 'review') {

        } elseif ((isset($_REQUEST['asof']) && $_REQUEST['asof'] == 'bydate') || (session()->has('asofDefault') && session('asofDefault') == 'bydate' && ! isset($_REQUEST['asof']))) {
            if (isset($_REQUEST['asof']) && $_REQUEST['asof'] == 'bydate') {
                $as_of_time = strtotime(date('Y-m-d H:i:s', strtotime($_REQUEST['asofdate'])));
                $as_of_clause = "and go_live_time < $as_of_time";
            } elseif (session('asofDefault') == 'bydate' && ! isset($_REQUEST['asof'])) {
                $as_of_time = strtotime(session('asofdateDefault'));
                $as_of_clause = "and go_live_time < $as_of_time";
            }

        } else {
            $as_of_clause = 'and go_live_time < ' . $as_of_time;
        }

        if (isset($filter['nofilter']) && $filter['nofilter']) {
            $as_of_time = time();
            $as_of_clause = 'and go_live_time < ' . $as_of_time;
        }

        $sql = "select u.topic_num, u.camp_num, u.title,u.camp_name, p.support_order, p.delegate_nick_name_id from support p,
        (select s.title,s.topic_num,s.camp_name,s.submit_time,s.go_live_time, s.camp_num from camp s,
            (select topic_num, camp_num, max(go_live_time) as camp_max_glt from camp
                where objector_nick_id is null $as_of_clause group by topic_num, camp_num) cz,
                (select t.topic_num, t.topic_name, t.namespace, t.go_live_time from topic t,
                    (select ts.topic_num, max(ts.go_live_time) as topic_max_glt from topic ts
                        where ts.namespace_id=$namespace and ts.objector_nick_id is null $as_of_clause group by ts.topic_num) tz
                            where t.namespace_id=$namespace and t.topic_num = tz.topic_num and t.go_live_time = tz.topic_max_glt) uz
                where s.topic_num = cz.topic_num and s.camp_num=cz.camp_num and s.go_live_time = cz.camp_max_glt and s.topic_num=uz.topic_num) u
        where u.topic_num = p.topic_num and ((u.camp_num = p.camp_num) or (u.camp_num = 1)) and p.nick_name_id = {$this->id} and
        (p.start < $as_of_time) and ((p.end = 0) or (p.end > $as_of_time)) and u.go_live_time < $as_of_time order by u.submit_time DESC";
        $results = DB::select($sql);
        $supports = [];
        foreach ($results as $rs) {
            $topic_num = $rs->topic_num;
            $camp_num = $rs->camp_num;
            $livecamp = Camp::getLiveCamp($topic_num, $camp_num, ['nofilter' => true]);
            $title = preg_replace('/[^A-Za-z0-9\-]/', '-', ($livecamp->title != '') ? $livecamp->title : $livecamp->camp_name);
            $topic_id = $topic_num . '-' . $title;
            $url = Camp::getTopicCampUrl($topic_num, $camp_num);
            if ($rs->delegate_nick_name_id && $camp_num != 1) {
                //$url = Camp::getTopicCampUrl($topic_num,$camp_num);//url('topic/' . $topic_id . '/' . $camp_num)
                $supports[$topic_num]['array'][$rs->support_order][] = ['camp_name' => $livecamp->camp_name, 'camp_num' => $camp_num, 'link' => $url, 'delegate_nick_name_id' => $rs->delegate_nick_name_id];
            } elseif ($camp_num == 1) {
                if ($rs->title == '') {
                    $topicData = \App\Model\v1\Topic::where('topic_num', '=', $topic_num)->where('go_live_time', '<=', time())->latest('submit_time')->get();
                    $liveTopic = Topic::getLiveTopic($topic_num, ['nofilter' => true]);
                    $title = preg_replace('/[^A-Za-z0-9\-]/', '-', $liveTopic->topic_name);
                    $topic_id = $topic_num . '-' . $title;
                }
                $supports[$topic_num]['camp_name'] = ($rs->camp_name != '') ? $livecamp->camp_name : $livecamp->title;
                $supports[$topic_num]['link'] = $url; //  url('topic/' . $topic_id . '/' . $camp_num);
                if ($rs->delegate_nick_name_id) {
                    $supports[$topic_num]['delegate_nick_name_id'] = $rs->delegate_nick_name_id;
                }
            } else {
                $supports[$topic_num]['array'][$rs->support_order][] = ['camp_name' => $livecamp->camp_name, 'camp_num' => $camp_num, 'link' => $url];
            }
        }

        return $supports;
    }

    /* get user data based on user_id */

    public function getUser()
    {
        return User::find($this->user_id);
    }

    public static function getUserByNickName($nick_id)
    {
        $nickname = self::find($nick_id);

        return User::find($nickname->user_id);
    }

    public static function getUsersByNickNameIds(array $nick_name_ids, array $columns = ['*'])
    {
        $userNickIds = self::whereIn('id', $nick_name_ids)->get();

        return User::select($columns)->whereIn('id', $userNickIds->pluck('user_id')->toArray())->get()->transform(function ($item) use ($userNickIds) {
            $item = $item->toArray();
            $item['nick_name_id'] = $userNickIds->where('user_id', $item['id'])->first()->id;

            return $item;
        })->values()->keyBy('nick_name_id')->all();
    }

    public static function getNickName($nick_id)
    {
        return self::find($nick_id);
    }

    public static function personNicknameIds()
    {
        if (Auth::check()) {
            return DB::table('nick_name')->where('user_id', Auth::user()->id)->orderBy('nick_name', 'ASC')->pluck('id')->toArray();
        }

        return [];
    }

    public static function getUserIDByNickName($nick_id)
    {

        $nickname = self::find($nick_id);
        if (! empty($nickname) && count($nickname) > 0) {
            return $nickname->user_id;
        }

        return null;
    }

    /**
     * By Reena Nalwa
     * Talentelgia
     *
     * @nickId is nick name ID of a user
     * return all nick names associated with that user
     */
    public static function personAllNicknamesByAnyNickId($nickId)
    {
        $userId = self::getUserIdBynickId($nickId);
        if (! empty($userId)) {
            return DB::table('nick_name')->select('id', 'nick_name')->where('user_id', $userId)->orderBy('nick_name', 'ASC')->get();
        } else {
            return [];
        }
    }
}
