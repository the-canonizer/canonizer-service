<?php

namespace App\Helpers;



class UtilHelper
{


    /**
     * get number of pages .
     *
     * @param int $totalCount
     * @param int $page_size
     *
     * @return int $number_of_pages
     */


    public function getNumberOfPages($totalCount, $pageSize)
    {
       $numberOfPages = $totalCount / $pageSize;
       $expArr = explode('.', $numberOfPages);
       $fractionalDigit = isset($expArr[1]) && $expArr[1] > 0 ? 1 : 0;
       $numberOfPages = $expArr[0] + $fractionalDigit;

       return $numberOfPages;
    }
}
