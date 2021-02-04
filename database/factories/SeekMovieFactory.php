<?php

namespace Database\Factories;

use App\SeekMovie;
use App\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SeekMovieFactory extends Factory
{

    protected $model = SeekMovie::class;

    public function definition()
    {
        return [
            'name'          => $this->faker->name,
            'description'   => $this->faker->text(200),
        ];
    }
}
