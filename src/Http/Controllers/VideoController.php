<?php

namespace Haxibiao\Media\Http\Controllers;

use App\Article;
use App\Category;
use App\Http\Requests\VideoRequest;
use App\Video;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $data = [];
        $site = cms_get_site();

        //置顶 - 合集
        $collections = \App\Collection::latest('updated_at')->take(6)->get();

        $movies = collect([]);
        // 置顶 - 电影
        if ($site && $site->stickyMovies()->byStickableName('视频页-电影')->count()) {
            $movies = $site->stickyMovies()
                ->byStickableName('视频页-电影')
                ->latest('stickables.updated_at')
                ->take(6)
                ->get();
        }
        if ($movies->isEmpty()) {
            $movies = indexTopMovies(6);
        }

        $articles = collect([]);
        //置顶 - 电影图解
        if ($site) {
            $articles = $site->stickyArticles()->whereType('diagrams')
                ->byStickableName('视频页-电影图解')
                ->latest('stickables.updated_at')
                ->take(6)
                ->get();
        }
        if ($articles->isEmpty()) {
            $articles = Article::query()->whereType('diagrams')
                ->latest('updated_at')
                ->take(6)
                ->get();
        }

        return view('video.index')
            ->with('data', $data)
            ->with('videos', [])
            ->with('collections', $collections)
            ->with('articles', $articles)
            ->with('movies', $movies);
    }

    function list(Request $request) {
        $videos = Article::with('user')
            ->with('category')
            ->with('video')
            ->orderBy('id', 'desc')
            ->where('status', '>=', 0)
            ->where('type', '=', 'video');

        //Search videos
        $data['keywords'] = '';
        if ($request->get('q')) {
            $keywords         = $request->get('q');
            $data['keywords'] = $keywords;
            $videos           = Article::with('user')
                ->with('category')
                ->with('video')
                ->orderBy('id', 'desc')
                ->where('status', '>=', 0)
                ->where(function ($query) use ($keywords) {
                    $query->where('title', 'like', "%$keywords%")
                        ->orWhere('description', 'like', "%$keywords%");
                });
        }
        $videos         = $videos->paginate(10);
        $data['videos'] = $videos;
        return view('video.list')->withData($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['video_categories'] = Category::pluck('name', 'id');
        return view('video.create')->withData($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(VideoRequest $request)
    {
        ini_set('memory_limit', '256M');
        $uploadSuccess = false;
        //如果是通过表单上传文件
        $file = $request->file('video');
        if ($file) {
            $hash  = md5_file($file->path());
            $video = Video::firstOrNew([
                'hash' => $hash,
            ]);
            if ($video->id) {
                abort(500, "相同视频已存在");
            }

            $title       = $request->title;
            $description = $request->description;
            if (empty($title)) {
                $title = str_limit($description, $limit = 20, $end = '...');
            }
            $video->title = $title;
            $video->save();

            //save article
            $article            = new Article();
            $article->user_id   = getUserId();
            $params['type']     = 'video';
            $params['cover']    = '/images/uploadImage.jpg'; //默认图
            $params['video_id'] = $video->id;
            $article->fill($params);
            //文章title
            $article->title       = $title;
            $article->description = $description;
            $article->save();

            //处理视频与分类的关系
            $article->saveCategory(request('categories'));
            $uploadSuccess = $video->saveFile($file);
        }
        if (!$uploadSuccess) {
            //视频上传失败
            abort(500, '视频上传失败');
        }
        return redirect()->to('/video');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $video = Video::findOrFail($id);
        if (empty($video->post)) {
            // abort(404, '视频对应的文章不见了');
        }
        $data['related_page'] = request()->get('related_page') ?? 0;
        return view('video.show')
            ->with('post', $video->post)
            ->with('video', $video)
            ->with('data', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $video = Video::with('article')->findOrFail($id);

        if (empty($video->article)) {
            abort(404, '视频对应的文章不见了');
        }

        //如果还没有封面，可以尝试sync一下vod结果了
        $covers = $video->jsonData('covers');
        //封面不够，就尝试检查同步截图结果
        if (empty($covers) || count($covers) < 5) {
            if (!$video->duration) {
                $video->startProcess();
            }

            $video->syncVodProcessResult();
        }
        $data['covers'] = $covers;
        return view('video.edit')
            ->withVideo($video)
            ->withData($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(VideoRequest $request, $id)
    {
        $video   = Video::findOrFail($id);
        $article = $video->article;

        //更新动态正文，上架下架状态
        $article->description = $request->body;
        $article->update($request->all());
        //维护专题关系
        $article->saveCategories(request('categories'));
        //改变相关状态
        $article->changeAction();

        //选取封面图
        if (!empty($request->cover)) {
            $video->cover  = $request->cover;
            $video->status = 1;
            $video->save();
            $article->cover = $request->cover;
            $article->save();
        } else if (!$video->cover) {
            //没封面的视频状态不能上架
            $video->status = 0;
            $video->save();
        }

        if (str_contains(url()->previous(), 'edit')) {
            return redirect('/video/' . $video->id);
        }
        //防止用户直接访问编辑界面无session导致页面报错
        return redirect()->back();
    }

    /**
     * 删除视频是软删除，同时删除磁盘上的视频文件
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $video = Video::findOrFail($id);

        //软删除 video
        $video->status = -1;
        $video->save();
        return redirect()->to('/video/list');
    }

    /* --------------------------------------------------------------------- */
    /* ------------------------------- 算法策略 ----------------------------- */
    /* --------------------------------------------------------------------- */
    /**
     * @Desc     获取相关视频
     * @DateTime 2018-07-24
     * @return   [type]     [description]
     */
    public function getRelationVideo($article, $need_length)
    {
        //关联视频
        $related_collection = new Collection([]);
        $article_ids        = [$article->id];
        //获取有视频的分类
        $categories = $article->categories()
            ->whereHas('videoArticles', function ($query) {
                $query->where('count_videos', '>', 0);
            })->get();

        if ($categories->isNotEmpty()) {
            //优先从最后一个分类中随机选择4个
            $category   = $categories->pop();
            $related_qb = $category
                ->videoArticles()
                ->where('articles.id', '<>', $article->id);
            $count              = $related_qb->count();
            $need_length        = $count >= $need_length ? $need_length : $count;
            $related_collection = $related_qb->take($need_length)->get();
            if ($related_collection->isNotEmpty()) {
                $article_ids = array_merge(
                    $related_collection->pluck('id')->toArray(), $article_ids
                );
                $related_collection = $related_collection;
            }
        }
        //视频数据不够时，还填充的视频数
        $fill_length = $need_length - count($related_collection);
        //主专题下文章数不够时候随机填充至4个()
        if ($fill_length > 0) {
            //暂时不实现复杂的策略,减少系统Query
            /*$category_ids   = $categories->pluck('id');
            $ids = \DB::table('article_category')
            ->whereIn('category_id',$category_ids)
            ->where('已收录')
            ->whereNotIn('article_id',$article_ids)
            ->pluck('article_id');
            if( count($ids)>0 ){
            $articles = Article::whereIn('id', $ids
            ->take($fill_length));

            $data['related'] = $data['related']
            ->merge($articles);
            }*/

            $related = Article::where('type', 'video')
                ->orderBy('id', 'desc')
                ->whereStatus(1)
                ->whereNotIn('id', $article_ids)
                ->orderBy(\DB::raw('RAND()'))
                ->take(100) //取最近上传的100个视频随机
                ->get()
                ->random($fill_length);
            $related_collection = $related_collection->merge($related);
        }
        return $related_collection;
    }

    public function processVideo($id)
    {
        $video = Video::findOrFail($id);
        if (empty($video->jsonData('covers'))) {
            processVideo($video);
            dd('请求成功,截图将在1-2分钟内返回,处理视频的速度取决于视频的大小,请勿一直刷新本页面.');
        } else {
            return $video->cover;
        }
    }
}
