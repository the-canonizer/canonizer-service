<?php

namespace App\Providers;

use App\Events\IncreaseTopicViewCountEvent;
use App\Listeners\IncreaseTopicViewCountListener;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\ExampleEvent::class => [
            \App\Listeners\ExampleListener::class,
        ],
        IncreaseTopicViewCountEvent::class => [
            IncreaseTopicViewCountListener::class
        ],
    ];
}
