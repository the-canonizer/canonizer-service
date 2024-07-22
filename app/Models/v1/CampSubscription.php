<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class CampSubscription extends Model
{
    protected $table = 'camp_subscription';
    public $timestamps = false;
    public function user() {
        return $this->belongsTo('App\Models\v1\User','id','user_id');
    }
}
