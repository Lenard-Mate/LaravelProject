<?php

use Faker\Generator as Faker;

$factory->define(App\Tag::class, function (Faker $faker) {

    $tagurile = array(
        'php', 'c', 'c++', 'java', 'c#', 'javascript', 'typescript', 'sql', 'maria_db', 'ajax',
        'jwt', 'node js', 'react', 'vue', 'angular', 'android', 'apache', 'windows', 'linux',
        'gradle', 'html', 'css', 'blade', 'laravel', 'rest api', 'graph ql', 'merkle tree', 'quees', 'DHCP',
        'rest api', 'ok http', 'retrofit', 'override', 'inputstram', 'data types', 'static vs mutable', 'private vs protected', 'regex', 'input-form',
        'sockets', 'threads', 'RMI', 'arraylist', 'matrix', 'canvas', 'vectors', 'json', 'xml', 'fetch request', 'get vs post', 'go', 'scala', 'godot', 'faker', 'unity',
        'addmob', 'conversion units', 'stack', 'mongo-db', 'maven', 'cassandra', 'springboot', 'intelij idea', 'netbeans', 'code blocks', 'softweare develeoment', 'students projects',
        'hosting', 'scoping in js', 'security', 'unit testing', 'github', 'micorservices', 'clean architecture', 'mvc', 'mathlab', 'assembly', 'arduino', 'OOP', 'domain driven design',
        'packet tracer'
    );
    return [
        // $table->increments('id');
        'name' => $tagurile[$faker->numberBetween(1, 20)],
        'question_id' => $faker->numberBetween(1, 20),

    ];
});
