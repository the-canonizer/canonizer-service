<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class EtherAddresses extends Model
{
    protected $table = 'ether_address';

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
