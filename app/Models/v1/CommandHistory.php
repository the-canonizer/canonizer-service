<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class CommandHistory extends Model
{
    protected $table = 'command_histories';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'parameters',
        'error_output',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'started_at' => 'integer',
        'finished_at' => 'integer',
    ];
}
