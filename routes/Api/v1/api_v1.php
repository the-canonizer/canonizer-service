<?php

$router->post('/simpleApi', ['uses' => 'Api\v1\TreeController@simpleApi']);

$router->group(['middleware'=>'cors','prefix' => 'v1', 'namespace' => 'Api\v1'], function () use ($router) {

    // for testing
    $router->post('/simpleApiTrack', ['uses' => 'TreeController@simpleApiTrack']);
    // trees
    $router->group(['prefix' => 'tree'], function () use ($router) {
        $router->post('/store', ['uses' => 'TreeController@store']);
        $router->post('/get', ['uses' => 'TreeController@find']);
    });

    // topics
    $router->group(['prefix' => 'topic'], function () use ($router) {
        $router->post('/getAll', ['uses' => 'TopicController@getAll']);
    });
});
