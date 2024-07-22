<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;
use DB;

class SupportInstance extends Model {

    protected $table = 'support_instance';
    public $timestamps = false;

    protected static $tempArray = [];


    public static function boot() {

    }

	public function nickname() {
        return $this->hasOne('App\Model\Nickname', 'id', 'nick_name_id');
    }
	public function camp() {
        return $this->hasOne('App\Model\Camp', 'camp_num', 'camp_num');
    }
	public function topic() {
        return $this->hasOne('App\Models\v1\Topic', 'topic_num', 'topic_num');
    }

	public function delegatednickname() {
        return $this->hasOne('App\Model\Nickname', 'id', 'delegate_nick_name_id');
    }

}
