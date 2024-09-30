<?php

namespace App\Models\v1;

use Jenssegers\Mongodb\Eloquent\Model;

class Timeline extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'timelines';

    protected $guarded = [];

    protected $dates = ['created_at'];
}
