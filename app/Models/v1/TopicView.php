<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class TopicView extends Model
{
    protected $table = 'topic_views';

    protected $dateFormat = 'U';

    protected $fillable = ['topic_num', 'camp_num', 'views'];
}
