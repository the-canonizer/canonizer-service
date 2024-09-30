<?php

namespace App\Console\Commands;

use App\Models\v1\Tree;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TruncateOldTrees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'truncate:trees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all old topics trees since past 30 days time period';

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
        /**
         * To clean up Mongo database, we need to constantly remove old topics trees
         * In Mongo we create cache of all topics (from MySQL database) on daily basis
         * Command should be run after 1 month
         */
        $oldTreesCount = Tree::where('created_at', '<', new DateTime('-30 days'))->count();

        if ($oldTreesCount > 0) {
            Tree::where('created_at', '<', new DateTime('-30 days'))->each(function ($tree) {
                $tree->delete();
            });

            Log::channel('scheduler')->info('Truncate old trees: Job executed successfully, deleted ' .$oldTreesCount. ' entries');
        } else {
            Log::channel('scheduler')->info('Truncate old trees: No old topic tree found');
        }
    }
}
