<?php

namespace App\Repository\Tree;

interface TreeInterface
{
    public function createTree($treeArr);
    public function upsertTree($treeArr, $conditions);
    public function findTree($conditions);
    public function getTreesWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize);
    public function getTreesWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter);
    public function getTotalTrees($namespaceId, $asofdate, $algorithm);
    public function getTotalTreesWithFilter($namespaceId, $asofdate, $algorithm, $filter);
}
