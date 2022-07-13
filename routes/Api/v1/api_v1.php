<?php
use Illuminate\Support\Facades\Artisan;

$router->group(['prefix' => 'v1', 'namespace' => 'Api\v1'], function () use ($router) {

    // trees
    $router->group(['prefix' => 'tree'], function () use ($router) {
        $router->post('/store', ['uses' => 'TreeController@store']);
        $router->post('/get', ['uses' => 'TreeController@find']);
        $router->get('/all', function () {
            Artisan::call('tree:all');
            dd('All topics trees generated successfully');
        });
    });

    // topics
    $router->group(['prefix' => 'topic'], function () use ($router) {
        $router->post('/getAll', ['uses' => 'TopicController@getAll']);
    });
});
