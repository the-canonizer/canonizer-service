<?php

namespace App\Model\v1;

use App\Facades\Services\TopicServiceFacade;
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
    protected $fillable = ['topic_num', 'tag_id', 'created_at', 'updated_at'];

    /**
     * Returns an array of tag IDs associated with the given topic number.
     *
     * @param int $topicNum The topic number to retrieve tags for.
     * @return array An array of tag IDs.
     */
    public static function getRelatedTagIds($topicNum)
    {
        $tags = [];

        $liveTopic = TopicServiceFacade::getLiveTopic($topicNum, time());
        
        if ($liveTopic) {
            $tags = self::where('topic_id', $liveTopic->id)->pluck('tag_id')->toArray();
        }

        return $tags;
    }
}
