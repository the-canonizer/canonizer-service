<?php


namespace App\Facades\Repositories;

use Illuminate\Support\Facades\Facade;

class TreeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'TreeRepository';
    }
}
