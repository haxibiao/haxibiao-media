<?php

namespace Database\Factories;

use App\AudioBook;
use App\Novel;
use Illuminate\Database\Eloquent\Factories\Factory;

class NovelFactory extends Factory
{

    protected $model = Novel::class;

    public function definition()
    {
        return [
            'name'   		=> $this->faker->title,
			'introduction'  => $this->faker->text(100),
			'cover'         => $this->faker->imageUrl(),
			'data'          => '[{"url": "https://cdn-xmly-com.diudie.com/1/1.mp3", "title": "第01集" ,"index": 1}, {"url": "https://cdn-xmly-com.diudie.com/1/2.mp3", "title": "第02集","index": 2}]'
        ];
    }
}
