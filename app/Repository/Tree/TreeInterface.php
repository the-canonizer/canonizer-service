<?php

namespace App\Repository\Tree;

interface TreeInterface
{
    public function createTree($treeArr);

    public function upsertTree($treeArr, $conditions);

    public function findLatestTree($conditions);
}
