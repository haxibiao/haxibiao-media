<?php

namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovelChapter extends Model
{
	protected $guarded = [];

	use \Haxibiao\Breeze\Traits\HasFactory;

	public function novel(): BelongsTo
	{
		return $this->belongsTo(Novel::class);
	}
}
