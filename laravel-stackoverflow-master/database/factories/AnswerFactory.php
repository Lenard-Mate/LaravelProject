<?php

use Faker\Generator as Faker;

$factory->define(App\Answer::class, function (Faker $faker) {
    return [
        'content' => $faker->realText(45),
        'question_id' => $faker->numberBetween(1, 16),
        'answer_id' => 0,
        'created_by' => $faker->numberBetween(1, 20),
        'status' => 1,
        // 'created_at' => date("Y-m-d H:i:s"),
        // 'updated_at' => date("Y-m-d H:i:s")
    ];
});
