<?php

$router->group(['prefix' => 'v2', 'namespace' => 'Api\v2'], function () use ($router) {
    $router->group(['prefix' => 'topic'], function () use ($router) {
        $router->post('/getAll', ['uses' => 'TopicController@getAll']);
    });
});
