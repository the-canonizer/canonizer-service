<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.
        // \Log::info("Request via store method: " .request());
        
        $this->app['auth']->viaRequest('api', function ($request) {

            $header = $request->header('X-Api-Token');
            
            if($header && $header == env('API_TOKEN')) {
                return true;
            }
            
            return null;
        });
    }
}
