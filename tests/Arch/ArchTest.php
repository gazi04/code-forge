<?php

/*
|--------------------------------------------------------------------------
| Architectural Tests
|--------------------------------------------------------------------------
|
| Static checks that lock in layer conventions: no stray debug calls, correct
| namespaces/suffixes, and no dependency leaks between layers. These run
| without the database, so they live outside Feature/ to skip RefreshDatabase.
|
*/

arch()->preset()->php();
arch()->preset()->security();

arch('no debug statements')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'var_export', 'die', 'exit'])
    ->not->toBeUsed();

arch('models')
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('controllers')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->toExtend('App\Http\Controllers\Controller')
    ->ignoring('App\Http\Controllers\Controller');

arch('services')
    ->expect('App\Services')
    ->toHaveSuffix('Service');

arch('enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('commands')
    ->expect('App\Console\Commands')
    ->toExtend('Illuminate\Console\Command');

arch('models avoid http layer')
    ->expect('App\Models')
    ->not->toUse('App\Http');
