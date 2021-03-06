<?php

namespace Haxibiao\Media\Http\Api;

use Exception;
use Haxibiao\Breeze\User;
use Haxibiao\Content\Article;
use Haxibiao\Content\Category;
use Haxibiao\Content\Post;
use Haxibiao\Media\Http\Controller;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpiderController extends Controller
{

    public function importDouyinSpider(Request $request)
    {
        try {
            $video_url = $request->video_url;
            $account   = $request->account;
            $metaInfo  = $request->description;

            $description = Str::replaceFirst('#在抖音，记录美好生活#', '', $metaInfo);
            if (Str::contains($description, '#')) {
                $description = Str::before($description, '#');
            } else {
                $description = Str::before($description, 'http');
            }

            $user = User::where('account', $account)
                ->orWhere('email', $account)
                ->firstOrFail();
            Auth::login($user);
            $hash  = md5_file($video_url);
            $video = Video::firstOrNew([
                'hash' => $hash,
            ]);
            $video->setJsonData('metaInfo', $metaInfo);
            $video->user_id = $user->id;
            $video->title   = $description;
            $video->save(); //不触发事件通知

            //本地存一份用于截图
            $cosPath     = 'video/' . $video->id . '.mp4';
            $video->path = $cosPath;
            Storage::disk('public')->put($cosPath, @file_get_contents($video_url));
            $video->disk = 'local'; //先标记为成功保存到本地
            $video->save();

            //将视频上传到cloud
            $cosDisk = Storage::cloud();
            $cosDisk->put($cosPath, Storage::disk('public')->get($cosPath));
            $video->disk = 'cos';
            $video->save();

            //TODO 分类关系
            $category = Category::firstOrNew([
                'name' => '我要上热门',
            ]);
            if (!$category->id) {
                $category->name_en = 'douyin';
                $category->status  = 1;
                $category->user_id = 1;
                $category->save();
            }

            $article              = new Article();
            $article->body        = $description;
            $article->status      = 1;
            $article->description = Str::limit($description, 280); //截取微博那么长的内容存简介
            $article->submit      = Article::SUBMITTED_SUBMIT; //直接发布
            $article->type        = 'post';
            $article->user_id     = $user->id;
            $article->video_id    = $video->id;
            $article->category_id = $category->id;
            $article->save();

            //TODO 奖励接口
            return 'ok';
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(500, $e->getMessage());
        }
    }

    //文件上传批量解析抖音链接
    public function importDouYin(Request $request)
    {
        $file = $request->file('file');
        if ($file->isValid()) {
            $str   = file_get_contents($file->getRealPath());
            $str   = trim($str);
            $array = explode(',', $str);

            $count = 0;
            for ($i = 0; $i < count($array); $i++) {
                try {
                    $spider_link = str_before($array[$i], '#');
                    $spider      = Spider::resolveDouyinVideo(getUser(), $spider_link);
                    $post        = Post::with('video')->firstOrNew(['spider_id' => $spider->id]);
                    //标签
                    if (str_contains($array[$i], "#")) {
                        $tags = str_after($array[$i], '#');
                        if ($tags) {

                            $tags     = str_replace(' ', '', $tags);
                            $tagNames = explode('#', $tags);
                            $post->tagByNames($tagNames ?? []);
                        }
                    }
                    $post->user_id = getUserId();
                    $post->save();
                    $count++;
                    sleep(5);
                } catch (Exception $e) {
                    continue;
                }
            }
            Log::info("成功解析" . $count . "条抖音分享");
        }
    }
}
