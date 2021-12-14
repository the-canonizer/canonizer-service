<?php


namespace App\Facades\Services;

use Illuminate\Support\Facades\Facade;

class CampServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CampService';
    }
}
