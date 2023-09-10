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
use App\Model\v1\Timeline;
use App\Services\AlgorithmService;
use Illuminate\Support\Facades\DB;
use TimelineRepository;

class CreateTopicTimelineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeline:all {topic_num?} {algorithm_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This commnad will create the tree timeline of all topics, but first removed all old topic timeline.';

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
        $data = [];
        $asOfTime =  time();
        $topic_num = $this->argument('topic_num') ?? NULL;
        $algorithm_id = $this->argument('algorithm_id') ?? '';
        $count =0;
        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [
                'asOfTime' => $asOfTime
            ],
            'started_at' => Carbon::now()->timestamp,
        ]);

        // If timeline:all command is already running, don't execute command
        $commandStatement = "php artisan timeline:all {topic_num?} {algorithm_id?}";
        $commandSignature = "timeline:all {topic_num?} {algorithm_id?}";
        $commandStatus = UtilHelper::getCommandRuningStatus($commandStatement, $commandSignature);

        //First Deleted all old topic Timeline related records.
        $algorithms = (new AlgorithmService())->getAlgorithmKeyList("timeline",$algorithm_id);
        /*
        * Delete specific topic number otherwise delete all topic in mongodb
        */
        /*
        foreach ($algorithms as $algorithm) {
            // Check the argument of id with command / else use the all.
            if (!empty($topic_num)) {
                $del = Timeline::where('algorithm_id', $algorithm)->where('topic_id','=', (int)$topic_num)->delete();
               
            }
            else{
                $del = Timeline::where('algorithm_id', $algorithm)->delete();
            }
        }
        */

        try {
            Log::info('timeline:all command started....');
            $start = microtime(true);
            $asOfTime =  time();
            if (!empty($topic_num)) {
                // get specific topic
                $topics = Topic::select(['topic_num'])->where('topic_num', $topic_num)->groupBy('topic_num')->get();
            }
            else{
                // get all topic
                $topics = Topic::select(['topic_num'])->orderBy('topic_num', 'ASC')->groupBy('topic_num')->get();
            }

            $lastRecord = 0; 
            if(count($topics)>1) {
                foreach ($topics  as $key => $topic) 
                {
                    // get the timeline tree from mongoDb
                    $conditions = TimelineService::getTopicConditions($topic->topic_num);
                    $mongoTree = TimelineRepository::findTimeline($conditions);
                    /* If the timeline is not in mongo for that asOfTime, then create in mongo and return the timeline */
                    if ((!$mongoTree || !count($mongoTree)))
                        break;
                    
                    $lastRecord =$topic->topic_num;

                }

                if($lastRecord){
                    // Delete specific topic
                    $del = Timeline::where('topic_id','=', (int)$lastRecord)->delete();
                }

            }
            else {
                    $lastRecord = $topic_num;
                    if($algorithm_id!="")
                        $del = Timeline::where('algorithm_id', $algorithm_id)->where('topic_id','=', (int)$topic_num)->delete();
            }

            foreach ($topics  as $key => $topic) 
            {
                Log::info('Topic number start - '.$topic->topic_num);
                // get the timeline tree from mongoDb
                //$conditions = TimelineService::getTopicConditions($topic->topic_num);
                //$mongoTree = TimelineRepository::findTimeline($conditions);
                /* If the timeline is not in mongo for that asOfTime, then create in mongo and return the timeline */
                //if ((!$mongoTree || !count($mongoTree))) {
                if($topic->topic_num < $lastRecord)
                    continue;
                $data = $this->getTopicHistory($topic_num=$topic->topic_num,$data);
                $data = $this->getCampHistory($topic_num=$topic->topic_num,$data);                
                $data = $this->getDirectSupportHistory($topic_num=$topic->topic_num,$data);
                $data = $this->getDelegatedSupportHistory($topic_num=$topic->topic_num,$data);
                $key_values = array_column($data, 'asOfTime'); 
                array_multisort($key_values, SORT_DESC, $data); //SORT_ASC SORT_DESC
                if(!empty($data)){
                    foreach($data as $k=>$result){
                        $count =$count +1;
                        $tree =  TimelineService::upsertTimeline($topic_num=$result['topic_num'], $algorithm=$algorithm_id, $asOfTime=$result['asOfTime'], $updateAll=0, $request = [], $message=$result['message'], $type=$result['type'], $id=$result['id'], $old_parent_id=$result['old_parent_id'], $new_parent_id=$result['new_parent_id'], $timelineType="history", $topic_name=$result['topic_name'], $camp_num=$result['camp_num'], $camp_name=$result['camp_name'],$key=count($data)-$k, $url=null);            
                        
                    
                    }

                } 
                $data = [];
                Log::info('Topic number end - '.$topic->topic_num. ' total time execution'. date("H:i:s",microtime(true) - $start) );
            }  

            Log::info('timeline:all command ended....');
            log::info('Total Count '.$count);
            $time_elapsed_secs = microtime(true) - $start;
            $this->info("timeline:all execution time : " .  date("H:i:s",$time_elapsed_secs));
            
        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }

    
    private function getTopicHistory($topic_num,$data)
    {
        $topic_information = DB::select('SELECT	
            a.id, 
            topic_num, 
            topic_name, 
            go_live_time,
            previous_topic_name, 
            submitter_nick_id,
            nick_name,
            STRCMP(topic_name, previous_topic_name),
            CASE 
                WHEN STRCMP(topic_name, previous_topic_name) = 0 THEN "same_topic_name"
                WHEN STRCMP(topic_name, previous_topic_name) IS NULL THEN "same_topic_name"
            ELSE "change_in_topic_name"
                END AS String_comparison
            FROM
            (
                SELECT 
                id, 
                topic_num, 
                topic_name, 
                go_live_time,
                submitter_nick_id,
                LAG(topic_name) OVER(ORDER BY go_live_time) AS previous_topic_name FROM topic
                WHERE topic_num = '.$topic_num.'  ORDER BY go_live_time 
            ) a, nick_name b
            WHERE a.submitter_nick_id = b.id'); //AND objector_nick_id IS NULL
           
        if(!empty($topic_information)){

            foreach($topic_information as $info){
                if($info->String_comparison=="same_topic_name" && ($info->previous_topic_name=="" || $info->previous_topic_name==NULL)){
                    $timelineMessage = $info->nick_name . " created a new topic ". $info->topic_name;
                    $type= "create_topic";
                    $data[] =array('topic_num'=>$info->topic_num, 'asOfTime'=>$info->go_live_time, 'message'=>$timelineMessage, 'type'=>$type, 'id'=>$info->id, 'old_parent_id'=>null, 'new_parent_id'=>null, 'new_parent_id'=>null, 'topic_name'=>$info->topic_name, 'camp_num'=>1, 'camp_name'=>"Aggreement");
                }
                else if($info->String_comparison=="change_in_topic_name"){
                    $timelineMessage = $info->nick_name . " updated the topic name from ". $info->previous_topic_name. " to ". $info->topic_name;
                    $type="update_topic";
                    $data[] =array('topic_num'=>$info->topic_num, 'asOfTime'=>$info->go_live_time, 'message'=>$timelineMessage, 'type'=>$type, 'id'=>$info->id, 'old_parent_id'=>null, 'new_parent_id'=>null, 'topic_name'=>$info->topic_name, 'camp_num'=>1, 'camp_name'=>"Aggreement");   
                }
            }
        }
        //print_r($data);die;
        return $data;  
    }

    private function getCampHistory($topic_num,$data)
    {               
        $camps_info = Camp::select(['id','camp_num'])
            ->where('topic_num', '=',$topic_num)
            ->where('camp_name', '!=', 'Agreement')
            ->where('objector_nick_id', '=', null)
            ->orderBy('id', 'asc')
            ->groupBy('camp_num')
            ->get(); 
        if(!empty($camps_info)) {
            foreach($camps_info as $camp){
                $camp_information = DB::select('SELECT
                topic_num,
                parent_camp_num,
                camp_num,
                camp_name,
                go_live_time,
                submitter_nick_id,
                nick_name,
                previous_camp_name,
                CASE
                  WHEN STRCMP (camp_name, previous_camp_name) = 0
                  THEN "same_camp_name"
                  WHEN STRCMP (camp_name, previous_camp_name) IS NULL
                  THEN "camp_created"
                  ELSE "change_in_camp_name"
                END AS camp_name_comparison,
                CASE
                  WHEN parent_camp_num = previous_parent_camp_num
                  THEN "same_parent_camp_num"
                  WHEN STRCMP (
                    parent_camp_num,
                    previous_parent_camp_num
                  ) IS NULL
                  THEN "same_parent_camp_num"
                  ELSE "change_in_parent_camp_num"
                END AS parent_camp_num_comparison
              FROM
                (SELECT
                  a.topic_num,
                  a.parent_camp_num,
                  a.camp_num,
                  a.camp_name,
                  a.go_live_time,
                  a.submitter_nick_id,
                  LAG(a.camp_name) OVER(
                ORDER BY a.topic_num,
                  a.parent_camp_num,
                  a.camp_num,
                  a.go_live_time
                ) AS previous_camp_name,
                LAG(a.parent_camp_num) OVER(
                ORDER BY a.topic_num,
                  a.parent_camp_num,
                  a.camp_num,
                  a.go_live_time
                ) AS previous_parent_camp_num
                FROM
                  (SELECT
                    topic_num,
                    parent_camp_num,
                    camp_num,
                    camp_name,
                    go_live_time,
                    submitter_nick_id
                  FROM
                    camp
                WHERE topic_num = '.$topic_num.'
                 AND camp_num = '.$camp['camp_num'].'
                    ) a,
                  (SELECT
                    topic_num,
                    parent_camp_num,
                    camp_num,
                    COUNT(camp_num) OVER(PARTITION BY camp_num) AS camp_count
                  FROM
                    camp
                  WHERE topic_num = '.$topic_num.'
                  AND camp_num = '.$camp['camp_num'].'
                    ) b
                WHERE a.topic_num = b.topic_num
                  AND a.camp_num = b.camp_num
                  AND a.parent_camp_num = b.parent_camp_num) a,
                nick_name b
                WHERE a.submitter_nick_id = b.id');
                if(!empty($camp_information)){
                    foreach($camp_information as $info){
                        $new_parent_id =null; 
                        $old_parent_id = null;
                        if($info->parent_camp_num_comparison=="change_in_parent_camp_num"){
                            $timelineMessage = $info->nick_name . " changed the parent of camp ". $info->camp_name;
                            $type="parent_change";
                            $new_parent_id =$info->parent_camp_num; 
                            $old_parent_id = $info->camp_num;
                            $data[] =array('topic_num'=>$info->topic_num, 'asOfTime'=>$info->go_live_time, 'message'=>$timelineMessage, 'type'=>$type, 'id'=>$camp->id, 'old_parent_id'=>$old_parent_id, 'new_parent_id'=>$new_parent_id, 'topic_name'=>null, 'camp_num'=>$info->camp_num, 'camp_name'=>$info->camp_name);
                        }
                        else if($info->camp_name_comparison=="camp_created"){ // create
                            $timelineMessage = $info->nick_name . " created a new Camp ". $info->camp_name;
                            $type="create_camp";
                            $data[] =array('topic_num'=>$info->topic_num, 'asOfTime'=>$info->go_live_time, 'message'=>$timelineMessage, 'type'=>$type, 'id'=>$camp->id, 'old_parent_id'=>$old_parent_id, 'new_parent_id'=>$new_parent_id, 'topic_name'=>null, 'camp_num'=>$info->camp_num, 'camp_name'=>$info->camp_name);
                        }
                        else if($info->camp_name_comparison=="change_in_camp_name"){
                            $timelineMessage = $info->nick_name . " updated the Camp name from ". $info->previous_camp_name. " to ". $info->camp_name;
                            
                            $type="update_camp"; 
                            $data[] =array('topic_num'=>$info->topic_num, 'asOfTime'=>$info->go_live_time, 'message'=>$timelineMessage, 'type'=>$type, 'id'=>$camp->id, 'old_parent_id'=>$old_parent_id, 'new_parent_id'=>$new_parent_id, 'topic_name'=>null, 'camp_num'=>$info->camp_num, 'camp_name'=>$info->camp_name);
                        }
                    }

                }
            }
        }
        return $data;   
    }

    private function getDirectSupportHistory($topic_num,$data) 
    {
        $support_info = DB::select("SELECT
                a.topic_num,
                a.camp_num,
                c.camp_name,
                a.nick_name_id,
                b.nick_name,
                `start` AS 'date',
                'direct_support_start'  
            FROM
                support a, nick_name b, camp c
            WHERE a.nick_name_id = b.id 
                AND a.topic_num = c.topic_num
                AND a.camp_num = c.camp_num
                AND a.topic_num = ".$topic_num." 
                AND delegate_nick_name_id = 0
                AND c.submit_time <= a.start 
            UNION
            SELECT
                a.topic_num,
                a.camp_num,
                c.camp_name,
                a.nick_name_id,
                b.nick_name,
                `end` AS 'date',
                'direct_support_end'
            FROM
                support a, nick_name b, camp c
            WHERE a.nick_name_id = b.id 
                AND a.topic_num = c.topic_num
                AND a.camp_num = c.camp_num
                AND a.topic_num = ".$topic_num."  
                AND `end` != 0
                AND delegate_nick_name_id = 0
                AND c.submit_time <= a.end");

        if(!empty($support_info))
        {
            foreach($support_info as $info) {
                $new_parent_id =null; 
                $old_parent_id = null;
                if($info->direct_support_start=="direct_support_start"){
                    $timelineMessage = $info->nick_name . " added their support on camp ". $info->camp_name;
                    $type="direct_support_added";
                }
                else{
                    $timelineMessage = $info->nick_name . " removed their support from camp ". $info->camp_name;
                    $type="direct_support_removed";
                }
                $data[] = array('topic_num'=>$info->topic_num, 'asOfTime'=>$info->date, 'message'=>$timelineMessage, 'type'=>$type, 'id'=>$info->camp_num, 'old_parent_id'=>$old_parent_id, 'new_parent_id'=>$new_parent_id, 'topic_name'=>null, 'camp_num'=>$info->camp_num, 'camp_name'=>$info->camp_name);
            }
        }
        
        return $data;
    }

    private function getDelegatedSupportHistory($topic_num,$data) 
    {
        $support_info = DB::select("SELECT
            support_id,
            topic_num,
            camp_num,
            (SELECT nick_name FROM nick_name WHERE id = a.nick_name_id) AS delegate_supporter,
                delegate_nick_name_id,
                nick_name,
                `start` AS 'date',
                'delegate_support_start'
            FROM
            support a, nick_name b
            WHERE a.delegate_nick_name_id = b.id
            AND topic_num = ".$topic_num." 
            AND delegate_nick_name_id != 0
            UNION
            SELECT
            support_id,
            topic_num,
            camp_num,
            (SELECT nick_name FROM nick_name WHERE id = a.nick_name_id) AS delegate_supporter,
            delegate_nick_name_id,
            nick_name,
            `end` AS 'date',
            'delegate_support_end'
            FROM
            support a, nick_name b
            WHERE a.delegate_nick_name_id = b.id
            AND topic_num = ".$topic_num."
            AND END != 0
            AND delegate_nick_name_id != 0");
        if(!empty($support_info))
        {
            foreach($support_info as $info) {
                $new_parent_id =null; 
                $old_parent_id = null;
                if($info->delegate_support_start=="delegate_support_start"){
                    $timelineMessage = $info->nick_name . " delegated their support to ". $info->delegate_supporter;
                    $type="delegate_support_added";
                }
                else{
                    $timelineMessage = $info->nick_name . " removed their delegate support";
                    $type="delegate_support_removed";
                }
               
                $data[] = array('topic_num'=>$info->topic_num, 'asOfTime'=>$info->date, 'message'=>$timelineMessage, 'type'=>$type, 'id'=>$info->support_id, 'old_parent_id'=>$old_parent_id, 'new_parent_id'=>$new_parent_id, 'topic_name'=>null, 'camp_num'=>$info->camp_num, 'camp_name'=>null);
            }
        }
        return $data;
    }

    /**
     * Get the url.
     *
     * @param int $topicNumber
     * @param int $campNumber
     * @param int $asOfTime
     * @param boolean $isReview
     *
     * @return string url
     */

     public function getTimelineUrl($topic_num, $topic_name, $camp_num, $camp_name, $topicTitle, $type)
     {
        try {
            $topic_name =isset($topic_name)?$topic_name:$topicTitle;
            $camp_num =isset($camp_num)?$camp_num:1;
            $camp_name =isset($camp_name)?$camp_name:"Agreement";
            $rootUrl = env('REFERER_URL');
            if($type ="create_topic" || $type ="create_camp" || $type ="parent_change"){
                $urlPortion = $rootUrl . '/topic/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name) . '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);

            }
            else if($type ="update_topic"){
                $urlPortion = $rootUrl . '/topic/history/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name);

            }
            else if($type ="update_camp"){
                $urlPortion = $rootUrl . '/camp/history/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name). '/' . $camp_num . '-' . $this->replaceSpecialCharacters($camp_name);

            }
            else{
                $urlPortion = $rootUrl . '/support/' . $topic_num . '-' . $this->replaceSpecialCharacters($topic_name). '/' . $camp_num . '-' . ($camp_name);

            }
            return $urlPortion;

        } catch (CampURLException $th) {
             throw new CampURLException("URL Exception");
         }
    }

    public function replaceSpecialCharacters($info){
        return preg_replace('/[^A-Za-z0-9\-]/', '-', $info);
    }

}

