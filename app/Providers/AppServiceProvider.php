<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use App\Core\Appointment\AppointmentManager;
use App\Core\Auth\TokenHandler;

class AppServiceProvider extends ServiceProvider
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
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(TokenHandler::class, function($app){
            return new TokenHandler(
                env('JWT_SECRET'), 
                env('JWT_EXPIRY_IN_SECONDS'), 
                env('APP_NAME')
            );
        });

        $this->app->singleton(AppointmentManager::class, function($app){
            $user = request()->get('user');
            //Incase shit goes sideways, it never does tho
            if(is_null($user)) throw new \Exception("This is a serious exception!");

            return new AppointmentManager($user, env('POSTAL_CODE'));
        });

        Schema::defaultStringLength(191);
    }
}
