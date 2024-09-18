<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class SharesAlgorithm extends Model
{
    protected $table = 'shares_algo_data';

    public $timestamps = false;

    protected static $tempArray = [];

    public function usernickname()
    {
        return $this->hasOne(Nickname::class, 'id', 'nick_name_id');
    }
}
