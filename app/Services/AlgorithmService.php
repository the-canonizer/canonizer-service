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
     * Get the blind_popularity algorithm score.
     * @param int $nickNameId
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     *
     * @return int $value = 1
     */

    public function blind_popularity(
        $nickNameId = null,
        $topicNumber = 0,
        $campNumber = 0,
        $asOfTime = null
    ) {
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

    public function mind_experts(
        $nickNameId = null,
        $topicNumber = 0,
        $campNumber = 0,
        $asOfTime = null
    ) {
        return  CampService::campTreeCount(81, $nickNameId, $asOfTime);
    }
}
