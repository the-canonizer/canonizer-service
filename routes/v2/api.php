<?php

use App\Http\Controllers\Api\v2\TopicController;
use Illuminate\Support\Facades\Route;

Route::controller(TopicController::class)->prefix('topic')->name('topic.')->group(function () {
    Route::post('getAll', 'getAll')->name('getAll');
});
