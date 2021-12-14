<?php


namespace App\Facades\Helpers;

use Illuminate\Support\Facades\Facade;

class DateTimeHelperFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'DateTimeHelper';
    }
}
