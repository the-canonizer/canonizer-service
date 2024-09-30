<?php

namespace App\Models\v1;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $dateFormat = 'U';

    protected $table = 'tags';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'is_active', 'parent_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'topics_tags', 'tag_id', 'topic_num');
    }

    public static function updateOrCreateTopicTags($tags, $topicNum)
    {

        try {
            foreach ($tags as $tagId) {
                TopicTag::updateOrCreate(
                    ['topic_num' => $topicNum, 'tag_id' => $tagId], // Unique criteria
                    [] // No additional attributes to update (optional)
                );
            }

            // After update of record , in case of any removal of tags we need to remove from topic_tags...
            $topicTags = TopicTag::whereNotIn('tag_id', $tags)->where('topic_num', $topicNum)->delete();

            return true;

        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }
}
