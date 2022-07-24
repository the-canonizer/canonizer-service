<?php

namespace App\Services;

use App\Model\v1\EtherAddresses;
use App\Model\v1\Nickname;
use App\Model\v1\SharesAlgorithm;
use App\Exceptions\Algorithm\ShareAlgorithmException;
use Illuminate\Support\Facades\Cache;
use DB;
use CampService;
use UtilHelper;

/**
 * Class AlgorithmService.
 *
 */
class AlgorithmService
{

    /**
    @return all the available algorithm key values used in Canonizer Service
     */
    public function getAlgorithmKeyList()
    {
        return array('blind_popularity', 'mind_experts','computer_science_experts');
    }

    /**
     * Get the blind_popularity algorithm score.
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $value = 1
     */

    public function blind_popularity($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return 1;
    }

    /**
     * Get the mind_experts algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public function mind_experts($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return CampService::campTreeCount(81, $nickNameId,$topicNumber,$campNumber, $asOfTime);
    }

    /**
     * Get the computer_science_expert algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public function computer_science_experts($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        return CampService::campTreeCount(124, $nickNameId, $topicNumber, $campNumber,$asOfTime);
    }

    /**
     * Get the phd algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function PhD($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 55 and camp_num =  5) or ' .
            '(topic_num = 55 and camp_num = 10) or ' .
            '(topic_num = 55 and camp_num = 11) or ' .
            '(topic_num = 55 and camp_num = 12) or ' .
            '(topic_num = 55 and camp_num = 14) or ' .
            '(topic_num = 55 and camp_num = 15) or ' .
            '(topic_num = 55 and camp_num = 17)';

        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the christian algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function christian($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 4) or ' .
            '(topic_num = 54 and camp_num = 5) or ' .
            '(topic_num = 54 and camp_num = 6) or ' .
            '(topic_num = 54 and camp_num = 7) or ' .
            '(topic_num = 54 and camp_num = 8) or ' .
            '(topic_num = 54 and camp_num = 9) or ' .
            '(topic_num = 54 and camp_num = 10) or ' .
            '(topic_num = 54 and camp_num = 11) or ' .
            '(topic_num = 54 and camp_num = 18)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the secular algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public function secular($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 3)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the mormon algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function mormon($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 7) or ' .
            '(topic_num = 54 and camp_num = 8) or ' .
            '(topic_num = 54 and camp_num = 9) or ' .
            '(topic_num = 54 and camp_num = 10) or ' .
            '(topic_num = 54 and camp_num = 11)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the Universal Unitarian algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function uu($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 15)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the atheist algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function atheist($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 54 and camp_num = 2) or ' .
            '(topic_num = 2 and camp_num = 2) or ' .
            '(topic_num = 2 and camp_num = 4) or ' .
            '(topic_num = 2 and camp_num = 5)';
        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the Transhumanist algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public function transhumanist($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 40 and camp_num = 2) or ' .
            '(topic_num = 41 and camp_num = 2) or ' .
            '(topic_num = 42 and camp_num = 2) or ' .
            '(topic_num = 42 and camp_num = 4) or ' .
            '(topic_num = 43 and camp_num = 2) or ' .
            '(topic_num = 44 and camp_num = 3) or ' .
            '(topic_num = 45 and camp_num = 2) or ' .
            '(topic_num = 46 and camp_num = 2) or ' .
            '(topic_num = 47 and camp_num = 2) or ' .
            '(topic_num = 48 and camp_num = 2) or ' .
            '(topic_num = 48 and camp_num = 3) or ' .
            '(topic_num = 49 and camp_num = 2) ';

        return CampService::campCount($nickNameId, $condition, false, $topicNumber, $campNumber, $asOfTime);
    }

    /**
     * Get the united_utah algorithm score.
     * United Utah Party Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public function united_utah($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 2)';
        return CampService::campCount($nickNameId, $condition, true, 231, 2, $asOfTime);
    }

    /**
     * Get the united_utah algorithm score.
     * Republican Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public function republican($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 3)';
        return CampService::campCount($nickNameId, $condition, true, 231, 3, $asOfTime);
    }

    /**
     * Get the united_utah algorithm score.
     * Democrat Algorithm using related topic and camp
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */

    public function democrat($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $condition = '(topic_num = 231 and camp_num = 4)';
        return CampService::campCount($nickNameId, $condition, true, 231, 4, $asOfTime);
    }

    /**
     * Get user ethers.
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $totalEthers
     */
    public function ether($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {

        $nickname = Nickname::find($nickNameId);
        $userId = null;

        if (!empty($nickname) && count(array($nickname)) > 0) {
            $ownerCode = $nickname->owner_code;
            $userId = UtilHelper::canonDecode($ownerCode);
        }

        $ethers = EtherAddresses::where('user_id', '=', $userId)->get();
        $totalEthers = 0;

        // $apiKey = '0d4a2732eca64e71a1be52c3a750aaa4';                      // Project Key
        // $etherUrl = 'https://mainnet.infura.io/v3/' . $apiKey;             // Ether Url

        $method = "POST";
        $url = env('ETHER_URL');
        $apiKey = env('ETHER_KEY');
        $etherUrl = $url . $apiKey;
        $headers = array(
            "Accept-Encoding: gzip, deflate",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Type: application/json",
            "Host: mainnet.infura.io",
        );

        foreach ($ethers as $ether) { // If users has multiple addresses

            $body = "{\"jsonrpc\":\"2.0\",\"method\":\"eth_getBalance\",\"params\": [\"$ether->address\", \"latest\"],\"id\":1}";
            $curlResponse = UtilHelper::curlExecute($method, $etherUrl, $headers, $body);

            if (!isset($response) || empty($response) || $response == '' || $response == null) {
                return 0;
            }

            $curlResultObj = json_decode($curlResponse);
            $balance = $curlResultObj->result;
            $totalEthers += (hexdec($balance) / 1000000000000000000);
        }

        return $totalEthers;
    }

    /**
     * Get canonizer shares algorithm score.
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function shares($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $algo = 'shares';
        return $this->shareAlgo($nickNameId, $topicNumber, $campNumber, $algo, $asOfTime);
    }

    /**
     * Get canonizer canonizer algorithm score
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function shares_sqrt($nickNameId = null, $topicNumber = 0, $campNumber = 0, $asOfTime = null)
    {
        $algo = 'shares_sqrt';
        return $this->shareAlgo($nickNameId, $topicNumber, $campNumber, $algo, $asOfTime);
    }

    /**
     * Get share algorithm score
     *
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $score
     */
    public function shareAlgo($nickNameId, $topicNumber = 0, $campNumber = 0, $algo = 'shares', $asOfTime)
    {

        try {
            $year = date('Y', $asOfTime);
            $month = date('m', $asOfTime);

            $shares = SharesAlgorithm::whereYear('as_of_date', '=', $year)
                ->whereMonth('as_of_date', '<=', $month)
                ->where('nick_name_id', $nickNameId)
                ->orderBy('as_of_date', 'ASC')
                ->get();

            $sumOfShares = 0;
            $sumOfSqrtShares = 0;

            if (count($shares)) {
                foreach ($shares as $s) {
                    $sumOfShares = $s->share_value; //$sumOfShares + $s->share_value;
                    $sumOfSqrtShares = number_format(sqrt($s->share_value), 2); //$sumOfSqrtShares+ number_format(sqrt($s->share_value),2);
                }
            }

            $condition = "topic_num = $topicNumber and camp_num = $campNumber";
            $sql = "select count(*) as countTotal,support_order,camp_num from support where nick_name_id = $nickNameId and (" . $condition . ")";
            $sql2 = "and ((start < $asOfTime) and ((end = 0) or (end > $asOfTime)))";

            $result = Cache::remember("$sql $sql2", 2, function () use ($sql, $sql2) {
                return DB::select("$sql $sql2");
            });

            $total = 0;
            if ($algo == 'shares') {
                // $total = $result[0]->countTotal * $sumOfShares;
                $total = $sumOfShares;
            } else {
                //$total = $result[0]->countTotal * $sumOfSqrtShares;
                $total = $sumOfSqrtShares;
            }

            $returnShares = $total;

            return ($returnShares > 0) ? $returnShares : 0;
        } catch (ShareAlgorithmException $th) {
            throw new ShareAlgorithmException($th->getMessage(), 403);
        }

    }

    /**
     * Get algorithms array based on $updateAll parameter
     *
     * @param boolean $updateAll
     * @param string $algorithm
     *
     * @return array $algorithmArr
     */

    public function getCacheAlgorithms($updateAll, $algorithm)
    {

        $algorithmArr = $this->getAlgorithmKeyList();

        if ($updateAll) {
            return $algorithmArr;
        }

        if (in_array($algorithm, $algorithmArr) && ($key = array_search($algorithm, $algorithmArr)) !== false) {
            return array($algorithmArr[$key]);
        }

        return $algorithmArr;
    }
}
