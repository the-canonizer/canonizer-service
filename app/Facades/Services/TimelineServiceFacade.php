<?php

namespace App\Facades\Services;

use Illuminate\Support\Facades\Facade;

class TimelineServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'TimelineService';
    }
}
