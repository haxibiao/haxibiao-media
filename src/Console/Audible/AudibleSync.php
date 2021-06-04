<?php

namespace Haxibiao\Media\Console\Audible;

use App\Audible;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\Exception;

class AudibleSync extends Command
{
    protected $signature = 'audible:sync';

	const DB_CONNECTION = 'mediachain';
	const DB_TABLE 		= 'audio';

    protected $description = '同步音频数据';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
    	$this->validateDBSchema();

		$this->prepareDBConfig();

        $this->info('开始同步音频数据。。');
		$count = 0;
        DB::connection(self::DB_CONNECTION)->table(self::DB_TABLE)->select('id', 'extra' , 'name', 'announcer', 'introduction','cover','type_name','data' ,'status')
			->where('status',1)->chunkById(100,function($audioes)use(&$count){
				foreach($audioes as $audio){
					try {
						$extra  = @json_decode($audio->extra);
						$status = data_get($extra,'status'); // 连载状态
						$isOver = $status=="完结"? true : false;

						$updatedAt = data_get($extra,'updated_at'); // 连载状态
						$updatedAt = Carbon::parse($updatedAt)->addMinutes(rand(1,3600))->toDateTimeString();

						$data = json_decode($audio->data);

						$audible = Audible::firstOrNew([
							'source_key'      => $audio->id,
						]);
						$audible->name        = $audio->name;
						$audible->introduction= $audio->introduction;
						$audible->announcer	= $audio->announcer;
						$audible->cover       = $audio->cover;
						$audible->type_names  = $audio->type_name;
						$audible->data          = $data;
						$audible->count_chapters= count($data);
						$audible->updated_at  = $updatedAt;
						$audible->created_at  = $updatedAt;
						$audible->is_over	    = $isOver;
						$audible->save(['timestamps'=>false]);

						$count++;
						$this->info('同步《' .$audible->name. '》音频资源成功。。这是第' . $count . '个');
					} catch (\Exception $exception) {
						$this->error('同步音频失败。。' .$exception->getMessage());
						continue;
					}
				}
        });
		$this->info('同步音频资源完成。。共' . $count . '个');
    }

    private function validateDBSchema(){
		if (!Schema::hasTable('audibles')) {
			throw new Exception('当前数据库 没有audibles表!');
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
