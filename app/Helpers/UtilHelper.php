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

     /**
     * get date string of when cron run on system .
     *
     * @return int $cronDate
     */
    public function getCronRunDateString()
    {
        $cronDate = env('CS_CRON_DATE');
        $cronDate = isset($cronDate) ? strtotime($cronDate) : strtotime(date('Y-m-d'));
        return $cronDate;
    }

    /**
     * Decode the base64 string.
     * @param string $code
     *
     * @return int $code
     */

    public function canonDecode($code = ''){
        $code = base64_decode($code);
        return (int) $code=str_replace("Malia","",$code);
    }

    /**
     * Excute the http calls
     * @param string $type (GET|POST|PUT|DELETE)
     * @param string $url
     * @param string $headers (Optional)
     * @param array $body (Optional)
     * @return mixed
     */
    public function curlExecute($type, $url, $headers=null, $body=null) {

        $options = array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => "",
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => $type,
            CURLOPT_POSTFIELDS      => $body,
            CURLOPT_REFERER         => env('APP_URL'),
            CURLOPT_HTTPHEADER      => $headers
        );

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $curlResponse = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return null;
        }
        else {
            return $curlResponse;
        }

    }
}
