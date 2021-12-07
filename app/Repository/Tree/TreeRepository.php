<?php

namespace App\Repository\Tree;


use App\Model\v1\Tree;
use App\Repository\Tree\TreeInterface;
use Illuminate\Http\Request;

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
}
