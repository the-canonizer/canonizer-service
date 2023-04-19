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

class ScoreupdateTopicTimelineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeline:score';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commnad will update scroe of timeline of all topics';

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
        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'started_at' => Carbon::now()->timestamp,
        ]);

        // If timeline:score command is already running, don't execute command
        $commandStatement = "php artisan timeline:score";
        $commandSignature = "timeline:score";

        $commandStatus = UtilHelper::getCommandRuningStatus($commandStatement, $commandSignature);
        try {
            Log::info('timeline:score command started....');
            $start = microtime(true);
            $namespaces = Namespaces::all();
            $asOfTime =  time();
            foreach ($namespaces as $value) {
                // get all topic associated with this namespace
                $topics = Topic::select(['topic_num', 'namespace_id', 'id'])
                    ->where(["namespace_id" => $value['id']])
                    ->groupBy('topic_num')
                    ->get();
                $this->createTopicTimelinesDesc($topics); 
            }
            Log::info('timeline:score command ended ....');
            
            $time_elapsed_secs = microtime(true) - $start;
            $this->info('timeline:score execution time: ' . $time_elapsed_secs);
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }

   
    private function createTopicTimelinesDesc($topics)
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
            }
        }
    }
}
