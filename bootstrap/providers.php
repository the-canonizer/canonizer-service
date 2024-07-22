<?php

return [
    // Packages
    MongoDB\Laravel\MongoDBServiceProvider::class,

    App\Providers\AppServiceProvider::class,
    App\Providers\CustomHelpersFacadeProvider::class,
    App\Providers\CustomRepositoryFacadeProvider::class,
    App\Providers\CustomServicesFacadeProvider::class
];
