<?php

namespace App\Model\v2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TopicView extends Model
{
    protected $table = 'topic_views';

    protected $dateFormat = 'U';

    protected $fillable = ['topic_num', 'camp_num', 'views'];

    /**
     * Retrieves the total view count for each topic in the provided array of topic numbers.
     *
     * @param array $topic_nums The array of topic numbers to retrieve view counts for.
     * @return Illuminate\Support\Collection The collection of topic numbers with their corresponding total view counts.
     */
    public static function getTopicViewCounts(array $topic_nums)
    {
        return TopicView::select('topic_num', DB::raw('SUM(views) AS view_count'))
            ->whereIn('topic_num', $topic_nums)
            ->groupBy('topic_num')
            ->get();
    }
}
