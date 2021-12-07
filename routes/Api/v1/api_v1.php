<?php



$router->group(['prefix' => 'v1', 'namespace' => 'Api\v1'], function () use ($router) {

    $router->group(['prefix' => 'tree'], function () use ($router) {
        $router->post('/store', ['uses' => 'TreeController@store']);
    });
});
