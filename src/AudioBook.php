<?php
namespace Haxibiao\Media;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Haxibiao\Breeze\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

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

	public function  chaptersOfAudioBookResolver($root, $args, $content, $info){


		$perPage 		= data_get($args,'count',15);
		$currentPage 	= data_get($args,'page',1);
		$sortOrder 		= data_get($args,'sortOrderOfChapters','ASC');
		$chapters 		= $root->data;

		if(blank($chapters)){
			return [
				'data' =>[],
				'paginatorInfo' => [
					'count' 		=> 0,
					'currentPage' 	=> $currentPage,
					'firstItem' 	=> null,
					'hasMorePages' 	=> false,
					'lastItem' 		=> null,
					'lastPage' 		=> 1,
					'perPage' 		=> $perPage,
					'total' 		=> 0,
				]
			];
		}
		// 自定义排序
		if($sortOrder == 'DESC'){
			$chapters = array_reverse($chapters);
		}

		// 自定义分页
		$total 			= count($chapters);
		$chapters 	  	= collect($chapters);

		$result = (new LengthAwarePaginator(
			$chapters->forPage($currentPage, $perPage),
			$total,
			$perPage,
			$currentPage
		));

		return [
			'data' =>$result->items(),
			'paginatorInfo' => [
				'count' 		=> $result->count(),
				'currentPage' 	=> $result->currentPage(),
				'firstItem' 	=> $result->firstItem(),
				'hasMorePages' 	=> $result->hasMorePages(),
				'lastItem' 		=> $result->lastItem(),
				'lastPage' 		=> $result->lastPage(),
				'perPage' 		=> $result->perPage(),
				'total' 		=> $result->total(),
			]
		];
	}

	public function resolveAudioBook($root, $args, $content, $info){
		$audioId = data_get($args, 'id');
		return static::find($audioId);
	}

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
