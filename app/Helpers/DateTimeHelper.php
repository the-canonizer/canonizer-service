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
        $asOfTime = date('Y-m-d');

        if (isset($request['asofdate'])) {
            $asOfTime =  $request['asofdate'];
        }
        $asOfTime = strtotime(date('Y-m-d', $asOfTime));

        return $asOfTime;
    }
}
