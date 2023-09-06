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
    // Get latest topics from MongoDB using Raw Aggregate Function by stages. #MongoDBRefactoring
    public function getTopicsWithPagination($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $nickNameIds, $asOf, $search = '', $filter = '', $applyPagination = true, $archive = 0)
    {
        $search = $this->escapeSpecialCharacters($search);

        try {
            // Track the execution time of the code.
            $start = microtime(true);

            // All the where clauses.
            $match = [];

            // Filter extracted from getTopicsWithPaginationWithFilter(...$params) at line 173
            if (isset($filter) && $filter != null && $filter != '') {
                $match['topic_score'] = [
                    '$gt' => $filter
                ];
            }

            if ($namespaceId !== '') {
                if ($asOf == 'review') {
                    $match['review_namespace_id'] = $namespaceId;
                } else {
                    $match['namespace_id'] = $namespaceId;
                }
            }

            if (!empty($nickNameIds)) {
                $match['created_by_nick_id'] = ['$in' => $nickNameIds];
            }

            if (isset($search) && $search != '') {
                if ($asOf == 'review') {
                    $searchField = 'tree_structure.1.review_title';
                } else {
                    $searchField = 'topic_name';
                }
                $match[$searchField] = [
                    '$regex' => $search,
                    '$options' => 'i'
                ];
            }

            if (isset($archive) &&  !$archive) {
                $match['tree_structure.1.is_archive'] = 0;
            }

            // all the fields required in the response
            $projection = [
                '_id' => 0,
                'id' => 1,
                'topic_id' => 1,
                'topic_score' => 1,
                'topic_full_score' => 1,
                'topic_name' => 1,
                'as_of_date' => 1,
                'namespace_id' => 1,
                'algorithm_id' => 1,
                'submitter_nick_id' => 1,
                'created_by_nick_id' => 1,
                'tree_structure.1.review_title' => 1
            ];

            // This is a aggregate function from MongoDB Raw. It contains on stages. Output of one stage will be input of next stage.
            $aggregate = [
                [
                    // Stage 1: get all records matches with algorithm_id
                    '$match' => [
                        'algorithm_id' => $algorithm
                    ]
                ],
                [
                    // Stage 2: Sort all the topic by as_of_date in descending order to get latest
                    '$sort' => ['as_of_date' =>  -1]
                ],
                [
                    // Stage 3: GroupBy topic_id, and filter specific fields with lastest record from each group
                    '$group' => [
                        '_id' => '$topic_id',
                        'id' => [
                            '$first' => '$_id'
                        ],
                        'as_of_date' => [
                            '$first' => '$as_of_date'
                        ],
                        'topic_score' => [
                            '$first' => '$topic_score'
                        ],
                        'topic_full_score' => [
                            '$first' => '$topic_full_score'
                        ],
                        'topic_name' => [
                            '$first' => '$topic_name'
                        ],
                        'topic_id' => [
                            '$first' => '$topic_id'
                        ],
                        'namespace_id' => [
                            '$first' => '$namespace_id'
                        ],
                        'review_namespace_id' => [
                            '$first' => '$review_namespace_id'
                        ],
                        'algorithm_id' => [
                            '$first' => '$algorithm_id'
                        ],
                        'tree_structure' => [
                            '$first' => '$tree_structure'
                        ],
                        'submitter_nick_id' => [
                            '$first' => '$submitter_nick_id'
                        ],
                        'created_by_nick_id' => [
                            '$first' => '$created_by_nick_id'
                        ],
                    ]
                ],
                [
                    // Stage 4: Apply further filters to the grouped records
                    '$match' => $match,
                ],
                [
                    // Stage 5: Only get required keys from the grouped records
                    '$project' => $projection
                ],
                [
                    // Stage 6: Sort the record in descending order by topic_score
                    '$sort' => [
                        'topic_score' => -1
                    ]
                ],
            ];

            if ($applyPagination) {
                $aggregate = array_merge($aggregate, [
                    [
                        // Stage 6: Skip certain records
                        '$skip' => $skip,
                    ],
                    [
                        // Stage 7: Limit the records.
                        '$limit' => $pageSize,
                    ]
                ]);
            }

            $aggregate = $this->filterEmptyMongoStages($aggregate);

            $record = $this->treeModel::raw(function ($collection) use ($aggregate) {
                return $collection->aggregate($aggregate);
            })->toArray();

            $time_elapsed_secs = microtime(true) - $start;
            return $record;
        } catch (\Throwable $th) {
            throw $th;
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
    // No Need Now. Because this code is already merged in getTopicsWithPagination(...$params).
    public function getTopicsWithPaginationWithFilter($namespaceId, $asofdate, $algorithm, $skip, $pageSize, $filter, $nickNameIds, $search = '', $asOf)
    {
        try {
            $nextDay = $asofdate + 86400;
            $record = $this->treeModel::where('algorithm_id', $algorithm)
                ->where('as_of_date', '>=', $asofdate)
                ->where('as_of_date', '<', $nextDay)
                ->where('topic_score', '>', $filter);

            /* CAN-1084 -- to get all topics using namespace that is in-review change
                Added the new key in prepareMongoArr (review_namespace_id)
                if as == review then fetch on base of review_namespace_id
            */
            if ($asOf == 'review') {
                $record->when($namespaceId !== '', function ($q) use ($namespaceId) {
                    $q->where('review_namespace_id', $namespaceId);
                });
            } else {
                $record->when($namespaceId !== '', function ($q) use ($namespaceId) {
                    $q->where('namespace_id', $namespaceId);
                });
            }

            $record->when(!empty($nickNameIds), function ($q) use ($nickNameIds) {
                $q->whereIn('created_by_nick_id', $nickNameIds);
            });

            if (isset($search) && $search != '') {
                $record = $record->where(($asOf == 'review') ? 'tree_structure.1.review_title' : 'topic_name', 'like', '%' . $search . '%');
            }

            $record = $record->project(['_id' => 0])
                ->skip($skip)
                ->take($pageSize)
                ->orderBy('topic_score', 'desc')
                ->get(['topic_id', 'topic_score', 'topic_full_score', 'topic_name', 'as_of_date', 'tree_structure.1.review_title']);
            return $record;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * get count topics with condition.
     *
     * @param int $namespaceId
     * @param int $asofdate
     * @param string $algorithm
     * @param string $search
     * @param string $filter
     *
     *
     * @return array Response
     */
    // Get latest topic count from MongoDB using Raw Aggregate Function by stages. #MongoDBRefactoring
    public function getTotalTopics($namespaceId, $asofdate, $algorithm, $nickNameIds, $asOf, $search = '', $filter = '', $archive = 0)
    {
        $search = $this->escapeSpecialCharacters($search);

        try {

            // Track the execution time of the code.
            $start = microtime(true);

            // All the where clauses.
            $match = [];

            // Filter extracted from getTopicsWithPaginationWithFilter(...$params) at line 173
            if (isset($filter) && $filter != null && $filter != '') {
                $match['topic_score'] = [
                    '$gt' => $filter
                ];
            }

            if ($namespaceId !== '') {
                if ($asOf == 'review') {
                    $match['review_namespace_id'] = $namespaceId;
                } else {
                    $match['namespace_id'] = $namespaceId;
                }
            }

            if (!empty($nickNameIds)) {
                $match['created_by_nick_id'] = ['$in' => $nickNameIds];
            }

            if (isset($search) && $search != '') {
                if ($asOf == 'review') {
                    $searchField = 'tree_structure.1.review_title';
                } else {
                    $searchField = 'topic_name';
                }
                $match[$searchField] = [
                    '$regex' => $search,
                    '$options' => 'i'
                ];
            }

            if (isset($archive) &&  !$archive) {
                $match['tree_structure.1.is_archive'] = 0;
            }

            // This is a aggregate function from MongoDB Raw. It contains on stages. Output of one stage will be input of next stage.
            $aggregate = [
                [
                    // Stage 1: get all records matches with algorithm_id
                    '$match' => [
                        'algorithm_id' => $algorithm
                    ]
                ],
                [
                    // Stage 2: Sort all the topic by as_of_date in descending order to get latest
                    '$sort' => ['as_of_date' =>  -1]
                ],
                [
                    // Stage 3: GroupBy topic_id, and filter specific fields with lastest record from each group
                    '$group' => [
                        '_id' => '$topic_id',
                        'id' => [
                            '$first' => '$_id'
                        ],
                        'as_of_date' => [
                            '$first' => '$as_of_date'
                        ],
                        'topic_score' => [
                            '$first' => '$topic_score'
                        ],
                        'topic_full_score' => [
                            '$first' => '$topic_full_score'
                        ],
                        'topic_name' => [
                            '$first' => '$topic_name'
                        ],
                        'topic_id' => [
                            '$first' => '$topic_id'
                        ],
                        'namespace_id' => [
                            '$first' => '$namespace_id'
                        ],
                        'review_namespace_id' => [
                            '$first' => '$review_namespace_id'
                        ],
                        'algorithm_id' => [
                            '$first' => '$algorithm_id'
                        ],
                        'tree_structure' => [
                            '$first' => '$tree_structure'
                        ],
                        'submitter_nick_id' => [
                            '$first' => '$submitter_nick_id'
                        ],
                        'created_by_nick_id' => [
                            '$first' => '$created_by_nick_id'
                        ],
                    ]
                ],
                [
                    // Stage 4: Apply further filters to the grouped records
                    '$match' => $match,
                ],
                [
                    // Stage 5: Count the filtered record and stored into record_count variable.
                    '$count' => "record_count"
                ]
            ];

            $aggregate = $this->filterEmptyMongoStages($aggregate);

            $recordCount = $this->treeModel::raw(function ($collection) use ($aggregate) {
                return $collection->aggregate($aggregate);
            });

            $time_elapsed_secs = microtime(true) - $start;

            return $recordCount[0]->record_count ?? 0;
        } catch (\Throwable $th) {
            throw $th;
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
    // No Need Now. Because this code is already merged in getTotalTopics(...$params).
    public function getTotalTopicsWithFilter($namespaceId, $asofdate, $algorithm, $filter, $nickNameIds, $search = '', $asOf)
    {
        try {
            $nextDay = $asofdate + 86400;
            $record = $this->treeModel::where('algorithm_id', $algorithm)
                ->where('as_of_date', '>=', $asofdate)
                ->where('as_of_date', '<', $nextDay)
                ->where('topic_score', '>', $filter);

            /* CAN-1084 -- to get all topics using namespace that is in-review change
                Added the new key in prepareMongoArr (review_namespace_id)
                if as == review then fetch on base of review_namespace_id
            */
            if ($asOf == 'review') {
                $record->when($namespaceId !== '', function ($q) use ($namespaceId) {
                    $q->where('review_namespace_id', $namespaceId);
                });
            } else {
                $record->when($namespaceId !== '', function ($q) use ($namespaceId) {
                    $q->where('namespace_id', $namespaceId);
                });
            }

            $record->when(!empty($nickNameIds), function ($q) use ($nickNameIds) {
                $q->whereIn('created_by_nick_id', $nickNameIds);
            });

            if (isset($search) && $search != '') {
                $record = $record->where(($asOf == 'review') ? 'tree_structure.1.review_title' : 'topic_name', 'like', '%' . $search . '%');
            }

            $record = $record->get(['topic_id', 'topic_score', 'topic_full_score', 'topic_name', 'as_of_date', 'tree_structure.1.review_title']);
            return $record;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function filterEmptyMongoStages(array $aggregate): array
    {
        foreach ($aggregate as $key => $stage) {
            $stageKey = array_key_first($stage);
            if (in_array($stageKey, ['$match'])) {
                if (count($stage[$stageKey]) == 0) {
                    unset($aggregate[$key]);
                }
            }
        }

        return array_values($aggregate);
    }

    private function escapeSpecialCharacters($inputString)
    {
        $charactersToReplace = [    '~',   '`',   '!',   '@',   '#',   '$',   '%',   '^',   '&',   '*',   '(',   ')',   '_',   '+',   '-',   '=',   '{',   '}',   '[',   ']',   ';',   '\'',   ':',   '\"',   ',',   '.',   '/',   '<',   '>',   '?',   '|'];
        $replacementCharacters = ['\\~', '\\`', '\\!', '\\@', '\\#', '\\$', '\\%', '\\^', '\\&', '\\*', '\\(', '\\)', '\\_', '\\+', '\\-', '\\=', '\\{', '\\}', '\\[', '\\]', '\\;', '\\\'', '\\:', '\\\"', '\\,', '\\.', '\\/', '\\<', '\\>', '\\?', '\\|'];

        return str_replace($charactersToReplace, $replacementCharacters, $inputString);
    }
}
