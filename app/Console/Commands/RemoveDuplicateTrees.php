<?php

namespace App\Console\Commands;

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
    protected $description = 'This command will remove all the duplicated documents in tree if any.';

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
        Log::info('Remove Duplication Command Started....');
        $start = microtime(true);

        $asOfTime = $this->argument('asOfTime') ?? NULL;
        $doNotDelete = $this->option('do-not-delete') ? true : false;

        // check the argument of asOfTime with command / else use the current time.
        if (!empty($asOfTime)) {
            $asOfTime = intval($asOfTime);
        } else {
            $asOfTime = time();
        }

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
        $this->info('Total Execution Time: ' . $time_elapsed_secs);
        Log::info('Remove Duplication Command Ended....');
    }

    private function getDistinctTreeAlgorithm()
    {
        $algorithmes = Tree::distinct()->select('algorithm_id')->get()->toArray();
        $algorithmes = array_reduce($algorithmes, 'array_merge', array());
        return $algorithmes;
    }
}
