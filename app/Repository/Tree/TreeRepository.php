<?php

namespace App\Repository\Tree;

use App\Model\v1\Tree;
use App\Repository\Tree\TreeInterface;

class TreeRepository implements TreeInterface
{

    protected $model;
    /**
     * Instantiate a new TreeRepository instance.
     *
     * @return void
     */
    public function __construct(Tree $tree)
    {
        $this->model = $tree;
    }

    /**
     * create a new tree.
     *
     * @param  array tree
     * @return boolean Response
     */

    public function createTree($tree)
    {

        try {
            $record = Tree::create($tree);
            return $record->wasRecentlyCreated;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * create or update a tree.
     *
     * @param  array tree
     * @param  array $conditions | assocative array
     *
     * @return boolean Response
     */

    public function upsertTree($treeArr, $conditions)
    {
        try {
            $record = Tree::updateOrCreate(
                $conditions,
                $treeArr
            );
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * find a tree.
     *
     * @param  array $conditions | assocative array
     *
     * @return array Response
     */

    public function findTree($conditions)
    {
        try {

            $record = Tree::where($conditions)->get();
            return $record;

        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

}
