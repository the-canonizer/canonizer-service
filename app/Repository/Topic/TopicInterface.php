<?php

namespace App\Repository\Topic;

interface TopicInterface
{
    // public function getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $nickNameIds, $search, $asOf);
    // public function getTopicsWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search, $asOf);
    public function getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $nickNameIds, $asOf, $search = '', $filter = '', $applyPagination = true);

    // public function getTotalTopics($namespaceId, $asofdate, $algorithm, $nickNameIds, $search, $asof);
    // public function getTotalTopicsWithFilter($namespaceId, $asofdate, $algorithm, $filter, $nickNameIds, $search, $asof);
    public function getTotalTopics($namespaceId, $asofdate, $algorithm, $nickNameIds, $asOf, $search = '', $filter = '');
}
