<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class UtilHelper
{
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
     * Excute the http calls
     * @param string $type (GET|POST|PUT|DELETE)
     * @param string $url
     * @param string $headers (Optional)
     * @param array $body (Optional)
     * @return mixed
     */
    public function curlExecute($type, $url, $headers = null, $body = null)
    {

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
        } else {
            return $curlResponse;
        }
    }

    /**
     * Get command (to create tree of all topics) status
     *
     * @param string command statement
     * @param string command signature
     *
     * @return bool command status
     */
    public function getCommandRuningStatus($statement, $signature)
    {

        $commandStatus = 0;

        exec("ps -ef | grep \"" . $signature . "\"", $output);

        if (is_array($output) && count($output) > 0) {
            foreach ($output as $row) {
                $contains = Str::contains($row, $statement);

                if ($contains) {
                    $commandStatus = 1;
                    break;
                }
            }
        }

        return $commandStatus;
    }

    /**
     * Exception Handling Response.
     * Required exception collection and error tracing.
     * In case of error tracing it will give additional tracing of error.
     */
    public function exceptionResponse($exception, $errorTracing = false)
    {
        $errResponse = [
            'error_code' => $exception->getCode() ?? 500,
            'message' => $exception->getMessage() ?? "Something went wrong",
            'data' => null,
            'error' => [
                'message' => $exception->getFile() . ' on line ' . $exception->getLine(),
                'errorTraced' => []
            ]
        ];
        if ($errorTracing) {
            $errResponse['error']["errorTraced"] = $exception->getTrace();
        }
        return $errResponse;
    }
}
