<?php
namespace App\Model\v1;
use MongoDB\Laravel\Eloquent\Model;

class Timeline extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'timelines';
    protected $guarded = [];
    protected $dates = ['created_at'];
}
