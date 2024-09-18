<?php

namespace App\Repository\Topic;

interface TopicInterface
{
    public function getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $nickNameIds, $asOf, $search = '', $filter = '', $applyPagination = true);

    public function getTotalTopics($namespaceId, $asofdate, $algorithm, $nickNameIds, $asOf, $search = '', $filter = '');
}
