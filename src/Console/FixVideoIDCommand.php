<?php


namespace Haxibiao\Media\Console;

use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Console\Command;

class FixVideoIDCommand extends Command
{
    protected $signature = 'haxibiao:video:fixVideoID';

    protected $description = '重新构建视频的VID(只针对抖音视频)';

    public function handle()
    {
        Video::orderBy('id','desc')->chunk(100,function($videos){
            foreach ($videos as $video){
                if(data_get($video,'vid')){
                    continue;
                }

                $spider = Spider::where('spider_id',$video->id)
                    ->first();
                $vid = data_get($spider,'data.raw.item_list.0.video.vid');
                if($vid){
                   $video->vid = $vid;
                   $video->saveDataOnly();
                   $this->info($video->id);
                   continue;
                }

                $path = $video->path;
                $isValidUrl = filter_var($path, FILTER_VALIDATE_URL);
                if(!$isValidUrl){
                    continue;
                }

                $content = @file_get_contents($path,false,null,0,204800);
                if(!$content){
                    continue;
                }
                $result =  mb_convert_encoding($content, 'UTF-8',
                    mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
                preg_match_all('/vid:(.+?)\x00/', $result, $matches);
                if(!$matches[1]){
                    continue;
                }

                $vid = $matches[1][0];

                if(!$vid){
                    continue;
                }
                $video->vid = $vid;
                $video->saveDataOnly();

                $this->info($video->id);
            }
        });

    }
}