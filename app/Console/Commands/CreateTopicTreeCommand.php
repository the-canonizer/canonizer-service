<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\v1\Namespaces;
use App\Model\v1\Topic;
use TreeService;
use Illuminate\Support\Facades\Log;

class CreateTopicTreeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tree:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commnad will create the tree of all topics';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //get all namespaces
        $namespaces = Namespaces::all();
        foreach ($namespaces as $value) {
            // get all topic associated with this namespace
            $topics = Topic::select(['topic_num', 'namespace_id', 'id'])
                ->where(["namespace_id" => $value['id']])
                ->groupBy('topic_num')
                ->get();
             $this->createLess166Topics($topics);
             $this->creategreater166Topics($topics);
        }
    }

    private function createLess166Topics($topics)
    {
        if (count($topics)) {
            // create the tree for every topic
            foreach ($topics  as $key => $value) {

                $asOfTime =  strtotime(date('Y-m-d'));
                $topic_num = $value['topic_num'];
                $updateAll = 1;

                if ($value['topic_num'] < 166) {
                    $tree =  TreeService::upsertTree($topic_num, "blind_popularity", $asOfTime, $updateAll);
                    Log::info($tree);
                }
            }
        }
    }
    private function creategreater166Topics($topics)
    {
        if (count($topics)) {
            // create the tree for every topic
            foreach ($topics  as $key => $value) {

                $asOfTime =  strtotime(date('Y-m-d'));
                $topic_num = $value['topic_num'];
                $updateAll = 1;

                if ($value['topic_num'] >= 166) {
                    $tree =  TreeService::upsertTree($topic_num, "blind_popularity", $asOfTime, $updateAll);
                    Log::info($tree);
                }
            }
        }
    }
}
