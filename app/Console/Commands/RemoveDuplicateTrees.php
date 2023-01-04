<?php

namespace App\Console\Commands;

use App\Model\v1\CommandHistory;
use App\Model\v1\Tree;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use UtilHelper;

class RemoveDuplicateTrees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tree:remove-duplicate {asOfTime?} {--do-not-delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will remove all the duplicated record in tree if any.
        {asOfTime?} is optional. If {asOfTime?} is not present, command will pick current system date and time and change it to start of the day.
        {--do-not-delete} is used to log all the duplicated records without deleting the record.';

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
        Log::info('Remove duplication command started...');
        $start = microtime(true);

        $asOfTime = $this->argument('asOfTime') ?? NULL;
        $doNotDelete = $this->option('do-not-delete') ? true : false;

        // check the argument of asOfTime with command / else use the current time.
        if (!empty($asOfTime)) {
            $asOfTime = intval($asOfTime);
        } else {
            $asOfTime = time();
        }

        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [
                'asOfTime' => $asOfTime,
                '{--do-not-delete}' => $doNotDelete
            ],
            'started_at' => Carbon::now()->timestamp,
        ]);

        try {
            $startOfTheDay = Carbon::parse($asOfTime)->startOfDay();
            // If tree:remove-duplicate command is already running, don't execute command
            $commandStatement = "php artisan tree:remove-duplicate";
            $commandSignature = "tree:remove-duplicate";

            $commandStatus = UtilHelper::getCommandRuningStatus($commandStatement, $commandSignature);

            // if (!$commandStatus) {

            $algorithmes = $this->getDistinctTreeAlgorithm();

            foreach ($algorithmes as $algorithm) {
                $documents = Tree::where('as_of_date', '=', $startOfTheDay->timestamp)
                    ->where('algorithm_id', $algorithm)->orderBy('topic_id')->get();

                $counted = collect($documents)->countBy('topic_id')->filter(function ($value, $key) {
                    return $value > 1;
                })->keys();

                $duplicatedDocuments = collect($documents)->whereInStrict('topic_id', $counted)->keyBy('topic_id')->pluck('_id');
                $test = collect($documents)->whereInStrict('topic_id', $counted)->keyBy('topic_id')->pluck('_id', 'topic_id');
                Log::info($algorithm . '=> ' . json_encode($test->all()));

                if (!$doNotDelete) {
                    $users = Tree::whereIn('_id', $duplicatedDocuments->all())->delete();
                }
            }

            if (!$doNotDelete) {
                $this->info('Data Deleted');
            } else {
                $this->info('Duplicated data logged.');
            }
            // }
            $time_elapsed_secs = microtime(true) - $start;
            $this->info('tree:remove-duplicate execution time: ' . $time_elapsed_secs);
            Log::info('Remove duplication command ended...');
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }

    private function getDistinctTreeAlgorithm()
    {
        $algorithmes = Tree::distinct()->select('algorithm_id')->get()->toArray();
        $algorithmes = array_reduce($algorithmes, 'array_merge', array());
        return $algorithmes;
    }
}
