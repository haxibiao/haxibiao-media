<?php

namespace Haxibiao\Media;

use App\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

//FIXME: 重构为 App\Article type=comic
/**
 * Class Comic of 漫画
 * @package App
 */
class Comic extends Model
{

    /**
     * 获取漫画的内容
     */
    public function comicDetail(): HasMany
    {
        return $this->hasMany('App\ComicDetail');
    }
}
