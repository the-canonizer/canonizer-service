<?php

use App\Http\Controllers\Api\v1\{TimelineController, TopicController, TreeController};
use App\Http\Middleware\EnsureTokenIsPresentAndValid;
use Illuminate\Support\Facades\{Artisan, Route};

Route::controller(TreeController::class)->name('tree.')->prefix('tree')->group(function () {
    Route::middleware(EnsureTokenIsPresentAndValid::class)->group(function () {
        Route::post('store', 'store')->name('store');
        Route::post('remove-sandbox-tree', [TopicController::class, 'removeCacheSpecificTopics'])->name('topic.remove-sandbox-tree');
    });

    Route::post('get', 'find')->name('get');
    Route::post('/all', 'treeAllCommand')->name('all');
});

Route::controller(TopicController::class)->prefix('topic')->name('topic.')->group(function () {
    Route::post('getAll', 'getAll')->name('getAll');
});

Route::controller(TimelineController::class)->prefix('timeline')->name('timeline.')->group(function () {
    Route::post('/get', 'find')->name('get');
    Route::get('/all', function () {
        ini_set('max_execution_time', 3000);
        $time_start = microtime(true);
        Artisan::call('timeline:all');
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        dd('<b>All topic timelines generated successfully. Execution Time is:</b> ' . ($execution_time * 1000) . 'Milliseconds');
    })->name('all');

    Route::get('/adding-specific-topic/{topic_num}/{algorithm_id}', function (string $topic_num = null, string $algorithm_id = null) {
        ini_set('max_execution_time', 3000);
        $time_start = microtime(true);
        Artisan::call('timeline:all ' . $topic_num . '  ' . $algorithm_id);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        dd(' Specific topic timelines generated successfully. Execution Time is:  ' . ($execution_time) . ' seconds');
    })->name('adding-specific-topic');

    Route::middleware(EnsureTokenIsPresentAndValid::class)->group(function () {
        Route::post('store', 'store')->name('store');
    });
});
