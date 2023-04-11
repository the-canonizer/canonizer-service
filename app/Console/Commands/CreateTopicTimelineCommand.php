<?php

namespace App\Console\Commands;

use App\Model\v1\CommandHistory;
use Illuminate\Console\Command;
use App\Model\v1\Namespaces;
use App\Model\v1\Topic;
use Carbon\Carbon;
use Exception;
use TimelineService;
use Illuminate\Support\Facades\Log;
use Throwable;
use UtilHelper;
use App\Model\v1\Camp;
use App\Model\v1\Nickname;

class CreateTopicTimelineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeline:all {asOfTime?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commnad will create the tree timeline of all topics';

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

        // If timeline:all command is already running, don't execute command
        $commandStatement = "php artisan timeline:all";
        $commandSignature = "timeline:all";

        $commandStatus = UtilHelper::getCommandRuningStatus($commandStatement, $commandSignature);
        try {
            Log::info('timeline:all command started....');
            $start = microtime(true);

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
                    $this->createTopicTimelines($topics, $asOfTime);
                    $this->createTopicTimelinesDesc($topics, $asOfTime); 
                }

                // In some rare cases, data is duplicated randomly. This commad is used remove duplicated tree data.
                $this->call('timeline:remove-duplicate', [
                    'asOfTime' => $asOfTime
                ]);
            // }
            
            $time_elapsed_secs = microtime(true) - $start;
            $this->info('timeline:all execution time: ' . $time_elapsed_secs);
            Log::info('timeline:all command ended....');
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }

    private function createTopicTimelines($topics, $asOfTime)
    {
        
        if (count($topics)) {
            // create the timeline for every topic
            foreach ($topics  as $key => $value) 
            {
                $camps_info = Camp::select(['topic_num', 'id','go_live_time','camp_name','submitter_nick_id'])
                                ->where('topic_num', '=',$value['topic_num'])
                                //->where('camp_name', '!=', 'Agreement')
                                ->where('objector_nick_id', '=', null)
                                ->orderBy('id', 'asc') //desc
                                ->get();  
                if(!empty($camps_info)){
                    foreach($camps_info as $camp){
                        $asOfTime = (int) $camp['go_live_time'];
                        //timeline start
                        $nickName = Nickname::getNickName($camp->submitter_nick_id)->nick_name;
                        $timelineMessage = $nickName . " create Camp ". $camp['camp_name'];
                        
                        $tree =  TimelineService::upsertTimeline($topic_num=$camp['topic_num'], "blind_popularity", $asOfTime, $updateAll=1, $request = [], $message=$timelineMessage, $type="create_camp", $id=$value['id'], $old_parent_id=null, $new_parent_id=null,$timelineType="history");
                    }
                }
                //Log::info($tree);
            }
        }
    }
    private function createTopicTimelinesDesc($topics, $asOfTime)
    {
        
        if (count($topics)) {
            // create the timeline for every topic
            foreach ($topics  as $key => $value) 
            {
              
                $camps_info = Camp::select(['topic_num', 'id','go_live_time','camp_name','submitter_nick_id'])
                                ->where('topic_num', '=',$value['topic_num'])
                                //->where('camp_name', '!=', 'Agreement')
                                ->where('objector_nick_id', '=', null)
                                ->orderBy('id', 'desc') //desc
                                ->get();  
                if(!empty($camps_info)){
                    foreach($camps_info as $camp){
                        $asOfTime = (int) $camp['go_live_time'];
                        //timeline start
                        $nickName = Nickname::getNickName($camp->submitter_nick_id)->nick_name;
                        $timelineMessage = $nickName . " create Camp ". $camp['camp_name'];
                        
                        $tree =  TimelineService::upsertTimeline($topic_num=$camp['topic_num'], "blind_popularity", $asOfTime, $updateAll=1, $request = [], $message=$timelineMessage, $type="create_camp", $id=$value['id'], $old_parent_id=null, $new_parent_id=null,$timelineType="history");
                    }
                }
                //Log::info($tree);
            }
        }
    }
}
