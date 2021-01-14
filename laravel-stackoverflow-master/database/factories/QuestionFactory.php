<?php

use App\Http\Controllers\UserController;
use Faker\Generator as Faker;
use App\Tag;
use App\Question;

function slugify($text)
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

$factory->define(App\Question::class, function (Faker $faker) {

    $titlul = $faker->realText(35);
    return [
        'title' => $titlul . '?',
        'content' => $faker->realText(50),
        'slug' => slugify($titlul),
        'status' => 1,
        'created_by' => $faker->numberBetween(1, 20),
    ];
});
