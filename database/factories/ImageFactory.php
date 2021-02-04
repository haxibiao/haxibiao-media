<?php

namespace Database\Factories;

use App\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{

    protected $model = Image::class;

    public function definition()
    {
        return [
            'title'   => $this->faker->title,
            'hash'    => $this->faker->sha1,
            'extension'    => 'jpg',
            'source_url'          => $this->faker->imageUrl(),
        ];
    }
}
