<?php

namespace Haxibiao\Media\Console\Novel;

use Carbon\Carbon;
use Haxibiao\Media\Novel;
use Haxibiao\Media\NovelChapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery\Exception;

class NovelSync extends Command
{
    protected $signature = 'novel:sync';

    protected $description = '同步内涵云小说资源';

    private $total 		= 0;
    private $failure	= 0;
    private $successful	= 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
		Log::info('----- STARTING THE PROCESS FOR SYNC SOURCE -----');
		list($usec, $sec) = explode(" ", microtime());
		$dtStart =  ((float)$usec + (float)$sec);

		$this->validateDBSchema();
		$this->prepareDBConfig();
		// 上方是打印日志，以及数据校验，阅读时可跳过


        \DB::connection('mediachain')->table('novels')
			->where('status',1) // status 为1 代表该数据已经在内涵云被检查过
			->chunkById(10, $this->importNeihanCloudNovelsFunc());

        // 下方是打印日志，Review时可跳过
		list($usec, $sec) = explode(" ", microtime());
		$dtEnd =  ((float)$usec + (float)$sec);

		$headers = ['共发现（部）','成功导入（部）', '失败（部）', '备注'];
		$orders = [
			["{$this->total}", "{$this->successful}","{$this->failure}","耗时".($dtEnd-$dtStart)."秒"],
		];
		$this->table($headers, $orders);

		Log::info("----- {$this->successful}部小说同步已处理,耗时".($dtEnd-$dtStart)."秒");
		Log::info('----- FINISHED THE PROCESS FOR SYNC SOURCE -----');
    }

	/**
	 * @return \Closure
	 */
    private function importNeihanCloudNovelsFunc(){
		return function ($novels){

			foreach ($novels as $novel) {
				$this->total++;
				$value_array = [];
				try {
					// 插入小说
					$model = $this->novelFromCloudInsertToCurrentDB($novel);
					$chaptersFromCloud = @json_decode($novel->data);
					if(blank($chaptersFromCloud)){
						continue;
					}
					// 批量插入章节
					$value_array = $this->novelChaptersFromCloudConvertToArray($chaptersFromCloud,$model->id);
					NovelChapter::insert($value_array);
				} catch (\Exception $e){
					$this->error("小说[$novel->id]批量插入失败，正在逐条插入");
					try{
						foreach ($value_array as $v) {
							// [novel_id,index] 是唯一索引
							NovelChapter::updateOrCreate([
								'novel_id' => $v['novel_id'],
								'index'    => $v['index']
							], $v);
						}
					} catch (\Exception $e) {
						// 逐条插入也失败，回滚数据
						$this->info("清空小说[$novel->id]所有章节，并重新获取");
						NovelChapter::where('novel_id', $novel->id)->delete();
						Novel::where('id', $novel->id)->delete();
						$this->failure++;
						continue;
					}
				}
				$this->successful++;
			}
		};
	}

	private function novelChaptersFromCloudConvertToArray($chaptersFromCloud,$novelId){
		$value_array = [];
    	// 批量插入章节
		collect($chaptersFromCloud)->sortBy('index')->each(function ($item)use(&$value_array,$novelId){
			$now = Carbon::now();
			$value_array[] = [
				'title' 		=> $item->name,
				'index'			=> $item->index,
				'url' 			=> $item->url,
				'novel_id' 		=> $novelId,
				'created_at' 	=> $now,
				'updated_at' 	=> $now
			];
		});
		return $value_array;
	}

	private function novelFromCloudInsertToCurrentDB($novel){
		$model = Novel::firstOrNew([
			'source_key' => $novel->id, // 内涵云的小说资源ID
		]);
		$model->fill([
			'name'  		=> $novel->name,
			'cover'         => $novel->cover,
			'introduction'  => $novel->introduction,
			'type_names'  	=> $novel->type_names,
			'author'        => $novel->author,
			'count_words'   => $novel->count_words,
			'count_chapters'=> $novel->count_chapters,
			'source'        => $novel->source,
			'is_over'       => $novel->is_over,
			'created_at'    => $novel->created_at,
		])->save(['timestamps'=>false]);
		return $model;
	}


	private function validateDBSchema(){
		if(
			!Schema::hasColumns('novels',[
				'name',
				'source_key',
				'introduction',
				'cover',
				'type_names',
				'author',
				'is_over',
				'count_chapters',
				'count_words',
				'status'
			])
		){
			throw new Exception('当前数据库没有novels表或字段缺失，请按要求数据库!');
		}

		if (
			!Schema::hasColumns('novel_chapters',[
				'novel_id','title','url','index'
			])
		) {
			throw new Exception('当前数据库没有novel_chapters表或字段缺失，请先修复数据库!');
		}
	}

	private function prepareDBConfig(){
		if (env('DB_PASSWORD_MEDIA') == null) {
			$db_password_media = $this->ask("请注意 env('DB_PASSWORD_MEDIA') 未设置，正在用env('DB_PASSWORD'), 如果需要不同密码请输入或者[enter]跳过");
			if ($db_password_media) {
				config(['database.connections.mediachain.password' => $db_password_media]);
				$this->confirm("已设置media的db密码，继续吗? ");
			}
		}
	}


}
