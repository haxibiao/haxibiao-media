<?php
namespace Haxibiao\Media\Scopes;

use Haxibiao\Media\Movie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class MovieStatusScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        //过滤出可展示的和带封面的
       return $builder->whereIn('status', [Movie::PUBLISH])->whereNotNull('cover');

    }
}
