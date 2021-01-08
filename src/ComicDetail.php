<?php

namespace Haxibiao\Media;

use App\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ComicDetail of 漫画详情
 * @package App
 */
class ComicDetail extends Model
{
    protected $table = 'comics_detail';

    /**
     * 获取此图片所属的漫画
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo('App\Comic');
    }
}
