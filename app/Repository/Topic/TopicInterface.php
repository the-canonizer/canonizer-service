<?php

namespace App\Repository\Topic;

interface TopicInterface
{
    public function getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $search);
    public function getTopicsWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $search);
    public function getTotalTopics($namespaceId, $asofdate, $algorithm, $search);
    public function getTotalTopicsWithFilter($namespaceId, $asofdate, $algorithm, $filter, $search);
}
