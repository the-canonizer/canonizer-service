<?php

namespace App\Models\v1;

use MongoDB\Laravel\Eloquent\Model;

class Tree extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'trees';

    protected $guarded = [];

    protected $dates = ['created_at'];
}
