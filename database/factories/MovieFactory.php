<?php

namespace Database\Factories;

use App\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovieFactory extends Factory
{
    protected $model = Movie::class;

    public function definition()
    {
        return [
            'name'          => $this->faker->name,
            'introduction'  => $this->faker->text(200),
            'cover'         => $this->faker->imageUrl(),
            'score'         => $this->faker->numberBetween(0,10),
            'status'        => $this->faker->numberBetween(0,1),
            'data'          => '[{"url": "https://cdn-youku-com.diudie.com/series/9429/index.m3u8", "name": "第01集"}, {"url": "https://cdn-youku-com.diudie.com/series/9430/index.m3u8", "name": "第02集"}]'
        ];
    }
}
