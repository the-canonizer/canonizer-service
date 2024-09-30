<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class UserTag extends Model
{
    protected $dateFormat = 'U';

    protected $table = 'user_tags';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'tag_id'];
}
