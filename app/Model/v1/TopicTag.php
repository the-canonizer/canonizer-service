<?php

namespace App\Model\v1;

use Illuminate\Database\Eloquent\Model;

class TopicTag extends Model
{
    protected $dateFormat = 'U';

    protected $table = 'topics_tags';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_num','tag_id','created_at', 'updated_at'];
}
