<?php

namespace BoundedContext\Laravel\Providers;

use BoundedContext\Laravel\Illuminate;
use BoundedContext\Map\Map;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class BoundedContextProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/bounded-context.php' => config_path('bounded-context.php'),
        ]);

        $this->publishes([
            __DIR__.'/../../migrations/' => database_path('/migrations')
        ], 'migrations');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Illuminate\Contracts\Bus\Dispatcher',
            'BoundedContext\Laravel\Bus\Dispatcher'
        );

        $projection_types = Config::get('bounded-context.projections');

        if(!$projection_types)
        {
            return;
        }
        
        foreach($projection_types as $projection_type)
        {
            foreach($projection_type as $projection => $implemented_projection)
            {
                $this->app->singleton($projection, $implemented_projection);
            }
        }
    }
}
