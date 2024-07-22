<?php

namespace App\Console\Commands;

use App\Model\v1\{Camp, CommandHistory, Tree};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveDbNonExistingTopicInMongo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:nonexistingtopics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is made for removal of non-existing topics of database in Mongo Cache.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Remove Db Non Existing topics in Mongo Started');
        $start = microtime(true);

        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [],
            'started_at' => Carbon::now()->timestamp,
        ]);

        try {
            $mongoDocuments = Tree::orderBy('topic_id')->groupBy('topic_id')->pluck('topic_id')->toArray();

            if(count($mongoDocuments)) {
                // Check all above mongo tree's exist in database or not...
                $checkTopicsInDb = Camp::select('topic_num')->distinct()->pluck('topic_num')->toArray();

                /// Get all existing topics in database...
                $dataDifferenceIds = array_diff($mongoDocuments, $checkTopicsInDb);
                Tree::whereIn('topic_id', $dataDifferenceIds)->delete();
            }

            $time_elapsed_secs = microtime(true) - $start;
            $this->info('remove:nonexistingtopics execution time: ' . $time_elapsed_secs);
            Log::info('Remove Db Non Existing topics in Mongo Ended...');
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }
}
