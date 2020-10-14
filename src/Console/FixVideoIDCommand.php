<?php


namespace Haxibiao\Media\Console;

use Haxibiao\Media\Video;
use Illuminate\Console\Command;

class FixVideoIDCommand extends Command
{
    protected $signature = 'haxibiao:video:fixVideoID';

    protected $description = '重新构建视频的VID(只针对抖音视频)';

    public function handle()
    {
        Video::chunk(100,function($videos){
            foreach ($videos as $video){
                $path = $video->path;
                $isValidUrl = filter_var($path, FILTER_VALIDATE_URL);
                if(!$isValidUrl){
                    continue;
                }

                $content = @file_get_contents($path);
                if(!$content){
                    continue;
                }
                $result =  mb_convert_encoding($content, 'UTF-8',
                    mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
                preg_match_all('/vid:(.+?)\x00/', $result, $matches);
                $vid = $matches[1];

                if(!$vid){
                    continue;
                }
                $video->vid = $vid;
                $video->saveDataOnly();
            }
        });

    }
}