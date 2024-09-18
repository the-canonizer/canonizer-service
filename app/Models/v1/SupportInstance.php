<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class SupportInstance extends Model
{
    protected $table = 'support_instance';

    public $timestamps = false;

    protected static $tempArray = [];

    public static function boot() {}

    public function nickname()
    {
        return $this->hasOne(Nickname::class, 'id', 'nick_name_id');
    }

    public function camp()
    {
        return $this->hasOne(Camp::class, 'camp_num', 'camp_num');
    }

    public function topic()
    {
        return $this->hasOne(Topic::class, 'topic_num', 'topic_num');
    }

    public function delegatednickname()
    {
        return $this->hasOne(Nickname::class, 'id', 'delegate_nick_name_id');
    }
}
