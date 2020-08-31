<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Media\Imageable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImageReFactoringCommand extends Command
{
    protected $signature = 'haxibiao:image:refactoring';

    protected $description = '重新构建imageable,可重复执行';

    public function handle()
    {
        // article_image
        if (Schema::hasTable('article_image')) {
            $this->comment('start Fix article_image');
            $articleImages = DB::table('article_image')->get();
            foreach ($articleImages as $articleImage) {
                $imageable = Imageable::firstOrNew([
                    'imageable_id'   => $articleImage->article_id,
                    'imageable_type' => 'articles',
                    'image_id'       => $articleImage->image_id,
                ]);
                $imageable->created_at = $articleImage->created_at;
                $imageable->updated_at = $articleImage->updated_at;
                $imageable->save(['timestamps' => false]);
            }
            $this->comment('end Fix article_image');
        }

        // feedback_image
        if (Schema::hasTable('feedback_image')) {
            $this->comment('start Fix feedback_image');
            $feedBackImages = DB::table('feedback_image')->get();
            foreach ($feedBackImages as $feedBackImage) {
                $imageable = Imageable::firstOrNew([
                    'imageable_id'   => $feedBackImage->feedback_id,
                    'imageable_type' => 'feedbacks',
                    'image_id'       => $feedBackImage->image_id,
                ]);
                $imageable->created_at = $feedBackImage->created_at;
                $imageable->updated_at = $feedBackImage->updated_at;
                $imageable->save(['timestamps' => false]);
            }
            $this->comment('end Fix feedback_image');
        }

        // imagesables
        if (Schema::hasTable('imageables')) {
            $this->comment('start Fix imageables');
            $imagesables = DB::table('imageables')->get();
            foreach ($imagesables as $imagesable) {
                $imageable = Imageable::firstOrNew([
                    'imageable_id'   => $imagesable->imageable_id,
                    'imageable_type' => $imagesable->imageable_type,
                    'image_id'       => $imagesable->image_id,
                ]);
                $imageable->created_at = $imagesable->created_at;
                $imageable->updated_at = $imagesable->updated_at;
                $imageable->save(['timestamps' => false]);
            }
            $this->comment('end Fix imageables');
        }
    }

}
