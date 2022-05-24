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


    public function getAsOfDate($asOfTime)
    {
        $asOfDate = date('Y-m-d');

        if (isset($asOfTime)) {
            $asOfDate =  $asOfTime;
        }
        $asOfDate = strtotime(date('Y-m-d', $asOfDate));    //Y-m-d H:i:s

        return $asOfDate;
    }

    /**
     * Get as of date time
     * 
     * @param Illuminate\Http\Request
     *
     * @return int $asOfDateTime
     */
    public function getAsOfDateTime($asOfTime)
    {
        $asOfDateTime = date('Y-m-d');

        if (isset($asOfTime)) {
            $asOfDateTime =  $asOfTime;
        }
        $asOfDateTime = strtotime(date('Y-m-d H:i:s', $asOfDateTime));    //Y-m-d H:i:s

        return $asOfDateTime;
    }
}
