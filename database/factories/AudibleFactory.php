<?php

namespace Database\Factories;

use App\Audible;
use Illuminate\Database\Eloquent\Factories\Factory;

class AudibleFactory extends Factory
{

    protected $model = Audible::class;

    public function definition()
    {
        return [
            'name'   		=> $this->faker->title,
			'introduction'  => $this->faker->text(100),
			'cover'         => $this->faker->imageUrl(),
			'data'          => '[{"url": "https://cdn-xmly-com.diudie.com/1/1.mp3", "name": "第01集"}, {"url": "https://cdn-xmly-com.diudie.com/1/2.mp3", "name": "第02集"}]'
        ];
    }
}
