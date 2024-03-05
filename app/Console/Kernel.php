<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CreateTopicTreeCommand;
use App\Console\Commands\RemoveDuplicateTrees;
use App\Console\Commands\RemoveNonLatestTreesCommand;
use App\Console\Commands\TruncateOldTrees;
use App\Console\Commands\CreateTopicTimelineCommand;
use App\Console\Commands\RemoveDbNonExistingTopicInMongo;
use App\Console\Commands\ScoreupdateTopicTimelineCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CreateTopicTreeCommand::class,
        TruncateOldTrees::class,
        RemoveDuplicateTrees::class,
        RemoveNonLatestTreesCommand::class,
        CreateTopicTimelineCommand::class,
        RemoveDbNonExistingTopicInMongo::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('truncate:trees')->daily();
        $schedule->command('tree:remove-non-latest')->daily();
    }
}
