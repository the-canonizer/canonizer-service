<?php

namespace App\Helpers;



class DateTimeHelper
{


    /**
     * get asOfTime .
     *
     * @param Illuminate\Http\Request
     *
     * @return int $asOfTime
     */


    public function getAsOfTime($request)
    {
        $asOfTime = time();

        if ((isset($request['asof']) && isset($request['asofdate']))) {
             $asOfTime = strtotime(date('Y-m-d H:i:s', strtotime($request['asofdate'])));
        }

        return $asOfTime;
    }
}
