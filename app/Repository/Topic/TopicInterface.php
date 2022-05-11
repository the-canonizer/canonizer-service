<?php

namespace App\Repository\Topic;

interface TopicInterface
{
    public function getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $nickNameIds, $search);
    public function getTopicsWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search);
    public function getTotalTopics($namespaceId, $asofdate, $algorithm, $nickNameIds, $search);
    public function getTotalTopicsWithFilter($namespaceId, $asofdate, $algorithm, $filter, $nickNameIds, $search);
}
