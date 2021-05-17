<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Media\Traits\SearchLogRepo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
