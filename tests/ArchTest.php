<?php

use Spatie\LaravelData\Data;

arch('Auth DTOs extend Spatie LaravelData')
    ->expect('App\\Domain\\Auth\\Data')
    ->toExtend(Data::class);

arch('Domain services have a handle method')
    ->expect('App\\Domain\\*\\Services')
    ->classes()
    ->toHaveSuffix('Service')
    ->toHaveMethod('handle');

arch('Controllers do not use response() helper directly')
    ->expect('App\\Http\\Controllers')
    ->not->toUse('response');

arch('App\\Models classes are proper classes')
    ->expect('App\\Models')
    ->toBeClasses();
