<?php
use Illuminate\Support\Facades\Artisan;

$router->group(['prefix' => 'v1', 'namespace' => 'Api\v1'], function () use ($router) {

    // trees
    $router->group(['prefix' => 'tree'], function () use ($router) {
        $router->post('/store', ['uses' => 'TreeController@store']);//'middleware' => 'auth',
        $router->post('/get', ['uses' => 'TreeController@find']);
        $router->get('/all', function () {
            ini_set('max_execution_time', 3000);
            $time_start = microtime(true); 
            Artisan::call('tree:all');
            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start);
            dd('<b>All topics trees generated successfully. Execution Time is:</b> '.($execution_time*1000).'Milliseconds');
        });
    });

    // topics
    $router->group(['prefix' => 'topic'], function () use ($router) {
        $router->post('/getAll', ['uses' => 'TopicController@getAll']);
    });

    // topicTimeline
    $router->group(['prefix' => 'timeline'], function () use ($router) {
        $router->post('/store', ['uses' => 'TimelineController@store']); //'middleware' => 'auth',
        $router->post('/get', ['uses' => 'TimelineController@find']);
        $router->get('/all', function () {
            ini_set('max_execution_time', 3000);
            $time_start = microtime(true); 
            Artisan::call('timeline:all');
            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start);
            dd('<b>All topic timelines generated successfully. Execution Time is:</b> '.($execution_time*1000).'Milliseconds');
        });
    });
    
});
