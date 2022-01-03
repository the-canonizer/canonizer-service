<?php

namespace App\Services;

use CampService;

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
        return array('blind_popularity', 'mind_experts');
    }

    /**
     * Get the blind_popularity algorithm score.
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
        return  CampService::campTreeCount(81, $nickNameId, $asOfTime);
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
