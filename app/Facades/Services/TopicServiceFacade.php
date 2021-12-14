<?php

namespace App\Facades\Services;

use Illuminate\Support\Facades\Facade;

class TopicServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'TopicService';
    }
}
