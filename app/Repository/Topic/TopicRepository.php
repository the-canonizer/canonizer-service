<?php

namespace App\Repository\Topic;

use App\Model\v1\Tree;
use App\Repository\Topic\TopicInterface;

class TopicRepository implements TopicInterface
{

    protected $treeModel;
    /**
     * Instantiate a new TopicRepository instance.
     *
     * @return void
     */
    public function __construct(Tree $tree)
    {
        $this->treeModel = $tree;
    }

    /**
     * get Topics with pagination.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param int $skip
     * @param int $pageSize
     * @param string $search
     *
     *
     * @return array Response
     */

    public function getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $nickNameIds, $search = '')
    {
        try {
            $nextDay = $asofdate + 86400;
            $record = $this->treeModel::where('algorithm_id', $algorithm)
                ->where('as_of_date', '>=', $asofdate)
                ->where('as_of_date', '<', $nextDay);
                
            $record->when($namespaceId !== '', function ($q) use($namespaceId) { 
                $q->where('namespace_id', $namespaceId);
            });

            $record->when(!empty($nickNameIds), function ($q) use($nickNameIds) { 
                $q->whereIn('submitter_nick_id', $nickNameIds);
            });
            
            if (isset($search) && $search != '') {
                $record = $record->where('topic_name', 'like', '%' . $search . '%');
            };

            $record = $record->project(['_id' => 0])
                ->skip($skip)
                ->take($pageSize)
                ->orderBy('topic_score', 'desc')
                ->get(['topic_id', 'topic_score', 'topic_name', 'as_of_date', 'tree_structure.1.review_title']);
                
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * get Topics with pagination with filter.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param int $skip
     * @param int $pageSize
     * @param float $filter
     * @param string $search
     *
     *
     * @return array Response
     */

    public function getTopicsWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search = '')
    {
        try {
            $nextDay = $asofdate + 86400;
            $record = $this->treeModel::where('algorithm_id', $algorithm)
                ->where('as_of_date', '>=', $asofdate)
                ->where('as_of_date', '<', $nextDay)
                ->where('topic_score', '>', $filter);

            $record->when($namespaceId !== '', function ($q) use($namespaceId) { 
                $q->where('namespace_id', $namespaceId);
            });

            $record->when(!empty($nickNameIds), function ($q) use($nickNameIds) { 
                $q->whereIn('submitter_nick_id', $nickNameIds);
            });

            if (isset($search) && $search != '') {
                $record = $record->where('topic_name', 'like', '%' . $search . '%');
            }

            $record = $record->project(['_id' => 0])
                ->skip($skip)
                ->take($pageSize)
                ->orderBy('topic_score', 'desc')
                ->get(['topic_id', 'topic_score', 'topic_name', 'as_of_date', 'tree_structure.1.review_title']);
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * get count topics with condition.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param string $search
     *
     *
     * @return array Response
     */

    public function getTotalTopics($namespaceId, $asofdate, $algorithm, $nickNameIds, $search = '') 
    {
        try {
            $nextDay = $asofdate + 86400;
            $record = $this->treeModel::where('algorithm_id', $algorithm)
                ->where('as_of_date', '>=', $asofdate)
                ->where('as_of_date', '<', $nextDay);

            $record->when($namespaceId !== '', function ($q) use($namespaceId) { 
                $q->where('namespace_id', $namespaceId);
            });    
            
            $record->when(!empty($nickNameIds), function ($q) use($nickNameIds) { 
                $q->whereIn('submitter_nick_id', $nickNameIds);
            });
            
            if (isset($search) && $search != '') {
                $record = $record->where('topic_name', 'like', '%' . $search . '%');
            }

            $record = $record->get(['topic_id', 'topic_score', 'topic_name', 'as_of_date', 'tree_structure.1.review_title']);
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
     * @param string $search
     *
     *
     * @return array Response
     */

    public function getTotalTopicsWithFilter($namespaceId, $asofdate, $algorithm, $filter, $nickNameIds, $search = '')
    {
        try {
            $nextDay = $asofdate + 86400;
            $record = $this->treeModel::where('algorithm_id', $algorithm)
                ->where('as_of_date', '>=', $asofdate)
                ->where('as_of_date', '<', $nextDay)
                ->where('topic_score', '>', $filter);

            $record->when($namespaceId !== '', function ($q) use($namespaceId) { 
                $q->where('namespace_id', $namespaceId);
            });

            $record->when(!empty($nickNameIds), function ($q) use($nickNameIds) { 
                $q->whereIn('submitter_nick_id', $nickNameIds);
            });
   
            if (isset($search) && $search != '') {
                $record = $record->where('topic_name', 'like', '%' . $search . '%');
            }

            $record = $record->get(['topic_id', 'topic_score', 'topic_name', 'as_of_date', 'tree_structure.1.review_title']);
            return $record;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
