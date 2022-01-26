<?php

namespace App\Repository\Tree;


use App\Model\v1\Tree;
use App\Repository\Tree\TreeInterface;
use Illuminate\Http\Request;
use PHPUnit\TextUI\XmlConfiguration\Group;

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
        $this->model =  $tree;
    }


    /**
     * create a new tree.
     *
     * @param  array tree
     * @return boolean Response
     */

    public  function createTree($tree)
    {

        try {
            $record =  Tree::create($tree);
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

    public  function upsertTree($treeArr, $conditions)
    {
        try {
            $record =  Tree::updateOrCreate(
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

    public  function findTree($conditions)
    {
        try {
            $record =  Tree::where($conditions)->get();
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * get tree with pagination.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param int $skip
     * @param int $pageSize
     *
     *
     * @return array Response
     */

    public  function getTreesWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize)
    {
        try {
            $record = $this->model::where('namespace_id', $namespaceId)
            ->where('algorithm_id', $algorithm)
            ->where('as_of_date','<=', $asofdate)
            ->project(['_id'=> 0])
            ->skip($skip)
            ->take($pageSize)
            ->orderBy('topic_score', 'desc')
            ->groupBy('topic_id')
            ->get(['topic_id','topic_score', 'topic_name', 'as_of_date']);
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * get tree with pagination.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param int $skip
     * @param int $pageSize
     * @param float $filter
     *
     *
     * @return array Response
     */

    public  function getTreesWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter)
    {
        try {
            $record = $this->model::where('namespace_id', $namespaceId)
            ->where('algorithm_id', $algorithm)
            ->where('as_of_date','<=', $asofdate)
            ->where('topic_score','>', $filter)
            ->project(['_id'=> 0])
            ->skip($skip)
            ->take($pageSize)
            ->orderBy('topic_score', 'desc')
            ->groupBy('topic_id')
            ->get(['topic_id','topic_score', 'topic_name', 'as_of_date']);
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * get count tree with condition.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     *
     *
     * @return array Response
     */

    public  function getTotalTrees($namespaceId, $asofdate, $algorithm)
    {
        try {
            $record = $this->model::where('namespace_id', $namespaceId)
            ->where('algorithm_id', $algorithm)
            ->where('as_of_date','<=', $asofdate)
            ->groupBy('topic_id')
            ->get(['topic_id','topic_score', 'topic_name', 'as_of_date']);
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }


    /**
     * get count tree with condition.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param float $filter
     *
     *
     * @return array Response
     */

    public  function getTotalTreesWithFilter($namespaceId, $asofdate, $algorithm, $filter)
    {
        try {
            $record = $this->model::where('namespace_id', $namespaceId)
            ->where('algorithm_id', $algorithm)
            ->where('as_of_date','<=', $asofdate)
            ->where('topic_score','>', $filter)
            ->orderBy('topic_score', 'desc')
            ->groupBy('topic_id')
            ->get(['topic_id','topic_score', 'topic_name', 'as_of_date']);
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

}
