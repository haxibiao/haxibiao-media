<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Media\Traits\SearchLogRepo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SearchLog extends Model
{
    use HasFactory;
    use SearchLogRepo;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class);
    }

    public static function getTypes()
    {
        return [
            "movies"     => "电影",
            "questions"  => "题目",
            "categories" => "题库|分类",
        ];
    }

    public function resolveSearchLogs($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // 对搜索词排序 + 只允许在7日内
        $query = SearchLog::orderBy('count','desc')->whereDate('updated_at','>',today()->subDay(7))->pluck('keyword')->toArray();
        $resulets = array_unique($query);

        // 判断该搜索词是否能对应影片
        $logs = [];
        foreach ($resulets as $resulet) {
            $movie = Movie::where('name',$resulet)->first();
            if(!$movie){
                continue;
            }
            $logs[] = $movie->name;
        }
        if(count($logs) > 6){
            return array_slice($logs,0,6);
        }  
        return $logs;
    }
}
