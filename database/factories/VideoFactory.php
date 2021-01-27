<?php

namespace Database\Factories;

use App\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VideoFactory extends Factory
{

    protected $model = Video::class;

    public function definition()
    {
        return [
            'user_id' => random_int(1, 3), //better override manually
            'title'   => Str::random(),
            'duration'=> random_int(10, 30),
            'status'  => 1,
            'hash'    => Str::random(32),
            'json'    => '',
        ];
    }
}
