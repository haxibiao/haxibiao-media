<?php
namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Haxibiao\Breeze\Model;

class AudioBook extends Model
{
    use HasFactory;

	const STATUS_OF_DISABLED 		= -1; // 下架处理
	const STATUS_OF_ERROR 			= -2; // 资源损坏、丢失、不完整
	const STATUS_OF_NOT_IDENTIFY 	= 0;  // 未识别
	const STATUS_OF_PUBLISH 		= 1;  // 正常上架

	protected $casts = [
		'data'         => 'json',
	];

	protected $guarded = [];

	public function resolveFilterAudioBook($root, $args, $content, $info){

		$announcer = data_get($args, 'announcer');
		$type_name = data_get($args, 'type_name');

		return static::whereStatus(static::STATUS_OF_PUBLISH)
			->when($type_name, function ($qb) use ($type_name) {
				return $qb->where('type_name', $type_name);
			})->when($announcer, function ($qb) use ($announcer) {
				return $qb->where('announcer', $announcer);
			});
	}
}
