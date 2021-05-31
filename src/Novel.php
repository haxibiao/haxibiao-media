<?php

namespace Haxibiao\Media;

use Haxibiao\Breeze\Model as BreezeModel;
use Haxibiao\Media\Traits\NovelAttrs as TraitsNovelAttrs;
use Haxibiao\Breeze\Traits\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Novel extends BreezeModel
{
    protected $guarded = [];
    use HasFactory;
    use TraitsNovelAttrs;

    public const STATUS_OF_PUBLISH = 1;
    public const STATUS_OF_DISABLE    = -1;

    public function chapters(): HasMany
    {
        return $this->hasMany(NovelChapter::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_OF_PUBLISH);
    }

	public function resolveChapters($root, $args, $content, $info){
    	return $root->chapters();
	}

	public function resolveFilterNovels($root, $args, $content, $info){
		$author = data_get($args, 'author');

		return static::whereStatus(static::STATUS_OF_PUBLISH)
			->when($author, function ($qb) use ($author) {
				return $qb->where('author', $author);
			});
	}
}
