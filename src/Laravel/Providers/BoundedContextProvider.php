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
            __DIR__.'../../config/events.php' => config_path('events.php'),
            __DIR__.'../../config/projections.php' => config_path('projections.php'),
            __DIR__.'../../config/workflows.php' => config_path('workflows.php'),
        ]);

        $this->publishes([
            __DIR__.'../../migrations/' => database_path('/migrations')
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

        $this->app->singleton('BoundedContext\Contracts\Map', function($app)
        {
            return new Map(Config::get('events'));
        });

        $projection_types = Config::get('projections');
        foreach($projection_types as $projection_type)
        {
            foreach($projection_type as $projection => $implemented_projection)
            {
                $this->app->singleton($projection, $implemented_projection);
            }
        }
    }
}
