<?php

namespace App\Console\Commands;

use App\Facades\Services\TreeServiceFacade;
use App\Model\v1\{CommandHistory, Namespaces, Topic};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateBotExcludedTopicTreeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Generates the human-only (bot-excluded) score variant for every topic tree and stores
     * it in the parallel "_excl_bots" fields on the existing Mongo tree documents. Run this
     * AFTER tree:all so the base documents already exist.
     *
     * @var string
     */
    protected $signature = 'tree:bot:all {asOfTime?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will create the bot-excluded (human-only) score variant of all topic trees';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $asOfTime = $this->argument('asOfTime') ?? null;
        // check the argument of asOfTime with command / else use the current time.
        if (empty($asOfTime)) {
            $asOfTime = time();
        }

        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [
                'asOfTime' => $asOfTime
            ],
            'started_at' => Carbon::now()->timestamp,
        ]);

        try {
            Log::info('tree:bot:all command started....');
            $start = microtime(true);

            //get all namespaces
            $namespaces = Namespaces::all();
            foreach ($namespaces as $value) {
                // get all topic associated with this namespace
                $topics = Topic::select(['topic_num', 'namespace_id', 'id'])
                    ->where(["namespace_id" => $value['id']])
                    ->groupBy('topic_num')
                    ->get();
                $this->createBotExcludedTrees($topics, $asOfTime);
            }

            $time_elapsed_secs = microtime(true) - $start;
            $this->info('tree:bot:all execution time: ' . $time_elapsed_secs);
            Log::info('tree:bot:all command ended....');
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }

    /**
     * Generate and upsert the bot-excluded score variant for each topic.
     * Mirrors CreateTopicTreeCommand's loop but passes $excludeBots = true to upsertTree, which
     * writes only the "_excl_bots" fields onto the existing tree document (default fields untouched).
     */
    private function createBotExcludedTrees($topics, $asOfTime)
    {
        if (count($topics)) {
            foreach ($topics as $value) {
                $topic_num = $value['topic_num'];
                $updateAll = 1;

                $tree = TreeServiceFacade::upsertTree($topic_num, "blind_popularity", $asOfTime, $updateAll, [], true);
                Log::info($tree);
            }
        }
    }
}
