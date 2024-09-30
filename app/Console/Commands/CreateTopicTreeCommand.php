<?php

namespace App\Console\Commands;

use App\Facades\Services\TreeServiceFacade;
use App\Models\v1\CommandHistory;
use App\Models\v1\Namespaces;
use App\Models\v1\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Laravel\Prompts\progress;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $asOfTime = $this->argument('asOfTime') ?? null;
        // check the argument of asOfTime with command / else use the current time.
        $asOfTime = ! empty($asOfTime) ? intval($asOfTime) : time();

        $commandHistory = (new CommandHistory)->create([
            'name' => $this->signature,
            'parameters' => [
                'asOfTime' => $asOfTime,
            ],
            'started_at' => Carbon::now()->timestamp,
        ]);

        try {
            Log::info('tree:all command started....');
            $start = microtime(true);

            progress(
                label: 'Making trees in the MongoDB',
                steps: Namespaces::all(),
                callback: function ($namespace, $progress) use ($asOfTime) {
                    $topics = Topic::select(['topic_num', 'namespace_id', 'id'])
                        ->where(['namespace_id' => $namespace['id']])
                        ->groupBy('topic_num')
                        ->get();

                    if ($topics) {
                        /**
                         * Create the tree for every topic
                         */
                        foreach ($topics as $topic) {
                            $updateAll = 1;

                            $tree = TreeServiceFacade::upsertTree($topic['topic_num'], 'blind_popularity', $asOfTime, $updateAll);
                            Log::info($tree);
                        }
                    }
                },
                hint: 'This may take some time.'
            );

            // In some rare cases, data is duplicated randomly. This commad is used remove duplicated tree data.
            $this->call('tree:remove-duplicate', [
                'asOfTime' => $asOfTime,
            ]);

            $time_elapsed_secs = microtime(true) - $start;
            $this->info('tree:all execution time: ' . $time_elapsed_secs);
            Log::info('tree:all command ended....');
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }
}
