<?php

namespace App\Repository\Tree;

use App\Models\v1\Tree;

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
     * @return bool Response
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
     * @param  array  $conditions  | assocative array
     * @return bool Response
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
     * @param  array  $conditions  | assocative array
     * @return array Response
     */
    // Find latest topic from MongoDB. #MongoDBRefactoring
    public function findLatestTree($conditions)
    {
        try {

            $start = microtime(true);

            // unset as_of_date condition because we have to find latest topic. #MongoDBRefactoring
            unset($conditions['as_of_date']);

            $record = Tree::where($conditions)->orderBy('as_of_date', 'desc')->limit(1)->get();

            $time_elapsed_secs = microtime(true) - $start;

            return $record;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * find a tree.
     *
     * @param  array  $conditions  | assocative array
     * @return array Response
     */
    public function findTree($conditions)
    {
        try {

            $record = Tree::where($conditions)->get();

            return $record;

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
