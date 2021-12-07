<?php

namespace App\Model\v1;

use Jenssegers\Mongodb\Eloquent\Model;
use PhpParser\Node\Stmt\TryCatch;

class Tree extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'trees';
    protected $guarded = [];
}
