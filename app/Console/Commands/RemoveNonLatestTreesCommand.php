<?php

namespace App\Console\Commands;

use App\Facades\Repositories\TopicRepositoryFacade;
use App\Models\v1\CommandHistory;
use App\Models\v1\Tree;
use App\Services\AlgorithmService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveNonLatestTreesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tree:remove-non-latest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will remove all the trees other than latest.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [],
            'started_at' => Carbon::now()->timestamp,
        ]);

        try {

            Log::info($this->signature . ' command started on..' . Carbon::now()->timestamp);
            $start = microtime(true);

            $algorithms = (new AlgorithmService())->getAlgorithmKeyList('tree');
            foreach ($algorithms as $algorithm) {
                $topicsWithScore = TopicRepositoryFacade::getTopicsWithPagination('', 0, $algorithm, 0, 0, '', 'default', '', '', false)->toArray();

                $topics = collect($topicsWithScore)->pluck('id')->toArray();
                dd($topics);

                Tree::whereNotIn('_id', $topics)->where('algorithm_id', $algorithm)->delete();
            }

            $time_elapsed_secs = microtime(true) - $start;
            $this->info($this->signature . ' execution time: ' . $time_elapsed_secs);

            Log::info($this->signature . ' command ended....');
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }
}
