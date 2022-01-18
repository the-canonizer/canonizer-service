<?php

namespace App\Repository\Tree;

interface TreeInterface
{
    public function createTree($treeArr);
    public function upsertTree($treeArr, $conditions);
    public function findTree($conditions);
    public function getTreesWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize);
    public function getTotalTrees($namespaceId, $asofdate, $algorithm);
}
