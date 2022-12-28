<?php

namespace App\Console\Commands;

use App\Model\v1\CommandHistory;
use Illuminate\Console\Command;
use App\Model\v1\Namespaces;
use App\Model\v1\Topic;
use Carbon\Carbon;
use Exception;
use TreeService;
use Illuminate\Support\Facades\Log;
use Throwable;
use UtilHelper;

class CreateTopicTreeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tree:all {asOfTime?}';

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
        $asOfTime = $this->argument('asOfTime') ?? NULL;
        // check the argument of asOfTime with command / else use the current time.
        if (!empty($asOfTime)) {
            $asOfTime =  $asOfTime;
        } else {
            $asOfTime =  time();
        }

        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [
                'asOfTime' => $asOfTime
            ],
            'started_at' => Carbon::now()->timestamp,
        ]);

        // If tree:all command is already running, don't execute command
        $commandStatement = "php artisan tree:all";
        $commandSignature = "tree:all";

        $commandStatus = UtilHelper::getCommandRuningStatus($commandStatement, $commandSignature);
        try {
            // if (!$commandStatus) {
                //get all namespaces
                $namespaces = Namespaces::all();
                // $asOfTime =  time();
                foreach ($namespaces as $value) {
                    // get all topic associated with this namespace
                    $topics = Topic::select(['topic_num', 'namespace_id', 'id'])
                        ->where(["namespace_id" => $value['id']])
                        ->groupBy('topic_num')
                        ->get();
                    $this->createLess166Topics($topics, $asOfTime);
                    $this->creategreater166Topics($topics, $asOfTime);
                }

                // In some rare cases, data is duplicated randomly. This commad is used remove duplicated tree data.
                $this->call('tree:remove-duplicate', [
                    'asOfTime' => $asOfTime
                ]);
            // }
            // throw new Exception('Test');
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }

    private function createLess166Topics($topics, $asOfTime)
    {
        if (count($topics)) {
            // create the tree for every topic
            foreach ($topics  as $key => $value) {

                $topic_num = $value['topic_num'];
                $updateAll = 1;

                if ($value['topic_num'] < 166) {
                    $tree =  TreeService::upsertTree($topic_num, "blind_popularity", $asOfTime, $updateAll);
                    Log::info($tree);
                }
            }
        }
    }
    private function creategreater166Topics($topics, $asOfTime)
    {
        if (count($topics)) {
            // create the tree for every topic
            foreach ($topics  as $key => $value) {

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
