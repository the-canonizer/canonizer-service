<?php

namespace App\Model\v1;

use Illuminate\Database\Eloquent\Model;

class CommandHistory extends Model
{
    protected $table = 'command_histories';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'as_of_date',
        'error_output',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'as_of_date',
        'started_at',
        'finished_at',
    ];
}
