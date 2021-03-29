<?php

namespace Haxibiao\Media\Http\Api;

use App\Article;
use App\Category;
use App\User;
use Haxibiao\Helpers\utils\FFMpegUtils;
use Haxibiao\Helpers\utils\VodUtils;
use Haxibiao\Media\Http\Controller;
use Haxibiao\Media\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{

    protected $responseData = ['status' => 'success', 'code' => 200, 'message' => ''];

    public function store(Request $request)
    {
        $user = getUser();
        //前端vod上传成功后保存视频信息
        if ($request->from == 'qcvod') {
            $video = Video::firstOrNew([
                'fileid' => $request->fileId,
            ]);

            $video->user_id = $user->id;
            $video->path    = $request->videoUrl;
            //$video->filename = $request->videoName;
            $video->disk = 'vod';
            $video->save();

            //处理视频封面
            VodUtils::makeCover($request->fileId);

            // if (env('APP_NAME_CN') == "答妹") {
            //     $metadata = ['"userId"' => $user->id, '"app' => '"答妹"'];
            //     dispatch_now(new \App\Jobs\AddMetadata($video->path, $video->fileid, $metadata));
            //     $video  = Video::find($video->id);
            //     return $video;
            // }
            return $video;
        }

        return '没有腾讯云视频id';
    }

    public function importVideo(Request $request)
    {
        if ($data = $request->get('data')) {
            $video = Video::firstOrNew([
                'path' => $data['path'],
            ]);
            try {
                $video->fill($data);
                $video->save();
                return 1;
            } catch (\Exception $ex) {
                return -1;
            }
        }
        abort(404);
    }

    public function uploadVideo(Request $request)
    {
        $root = $request->root();
        if ($video = $request->file('video')) {

            //正式环境 禁止通过upload || video 二级域名传上来的视频.
            if (is_prod_env() && !preg_match('#//(upload|video).*?#', $root)) {
                abort(500, "上传失败!");
            }
            if ($video->extension() != 'mp4') {
                abort(500, '视频格式不正确,请上传正确的MP4视频!');
            }

            $video = Video::saveVideoFile($video, $request->input(), $request->user());

            return $video;
        }
        abort(500, "没上传视频文件过来");
    }

    //这个XXM视图用cos处理视频时测试用
    public function cosHookVideo(Request $request)
    {
        $inputs = $request->input();

        $playUrl  = array_get($inputs, 'playurl');
        $cosAppId = env('COS_APP_ID');
        $bucket   = env('COS_BUCKET');

        Log::channel('cos_video_hook')->info($inputs);

        //COS配置
        if (!is_null($cosAppId) && !is_null($bucket) && !is_null($playUrl)) {
            //获得文件名称
            $bucketPrefix = sprintf('/%s/%s/', $cosAppId, $bucket);
            $fileSuffix   = '.f30.mp4';
            $videoPath    = str_replace([$bucketPrefix, $fileSuffix], '', $playUrl);

            //获取视频
            $video = Video::where('path', $videoPath)->first();
            if (!is_null($video)) {
                $json                   = $video->json;
                $json->transcode_hd_mp4 = str_replace($bucketPrefix, '', $playUrl);
                $video->json            = $json;
                $video->syncStatus();
                $video->save();

                return [$json];
            }
        }
    }

    public function index(Request $request)
    {
        //热门专题，简单规则就按视频数多少来判断专题是否热门视频专题
        $categories = Category::orderBy('count_videos', 'desc')->take(3)
            ->get();
        $data = [];
        foreach ($categories as $category) {
            $articles = $category->containedVideoPosts()
                ->where('status', '>', 0)
                ->orderByDesc('hits')
                ->take(3)
                ->get();
            if (!$articles->isEmpty()) {
                $data[$category->name] = $articles;
            }
        }
        return view('video.index')->with('data', $data);
    }

    public function show($id)
    {
        $video = Video::with('article')
            ->with('user')
            ->findOrFail($id);

        //check article exist and status
        $article = $video->article;
        if (empty($article)) {
            abort(404);
        }
        if ($article->status < 1) {
            if (!canEdit($article)) {
                abort(404);
            }
        }

        $data['related_page'] = request()->get('related_page');
        //记录用户浏览记录
        $article->recordBrowserHistory();

        return view('video.show')
            ->withVideo($video)
            ->withData($data);
    }

    public function getLatestVideo(Request $request)
    {
        //FIXME: stick 逻辑
        $qb    = \App\Post::whereStatus(1)->latest('updated_at')->with(['video', 'user']);
        $posts = $qb->paginate(9);
        //兼容vue读取 article.cover 的
        foreach ($posts as $post) {
            $post->cover        = $post->cover;
            data_set($post,'user.avatar',data_get($post,'user.avatar'));
        }
        return $posts;
    }

    public function showByVideoHash($hash)
    {
        $video       = Video::where('hash', $hash)->first();
        $qcvodFileid = data_get($video, 'qcvod_fileid');
        if ($qcvodFileid) {
            return $qcvodFileid;
        }
        return null;
    }

    public function resolveMetadata(Request $request)
    {
        /**
         * FIXME::其实可以前端上传 给我url也可以直接解析 服务器可以不做io操作了
         *  问题就是可能会有3份视频文件
         * 1. 视频原作者（抖音粘贴or自己上传的时候就会有一份原文件和添加了metadata的文件)
         * 2. 被邀请用户上传的文件
         */
        $uploadFile = $request->file('video');
        $result     = Storage::disk('public')->put('video', $uploadFile->get());
        if ($result) {
            $path = storage_path('app/public/video');
            $data = FFMpegUtils::getMediaMetadata($path);
            $json = $data['format']['tags']['comment'];
            return json_decode($json, true);
        }
    }
}
