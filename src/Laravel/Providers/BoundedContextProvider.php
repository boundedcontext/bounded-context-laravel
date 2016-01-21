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
            __DIR__.'/../../config/events.php' => config_path('events.php'),
        ]);

        $this->publishes([
            __DIR__.'/../../config/commands.php' => config_path('commands.php'),
        ]);

        $this->publishes([
            __DIR__.'/../../config/projections.php' => config_path('projections.php'),
        ]);

        $this->publishes([
            __DIR__.'/../../config/players.php' => config_path('players.php'),
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
            'BoundedContext\Contracts\Bus\Dispatcher',
            'BoundedContext\Laravel\Bus\Dispatcher'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Generator\Identifier',
            'BoundedContext\Laravel\Generator\Uuid'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Generator\DateTime',
            'BoundedContext\Laravel\Generator\DateTime'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Event\Version\Factory',
            'BoundedContext\Laravel\Event\Version\Factory'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Event\Snapshot\Factory',
            'BoundedContext\Laravel\Event\Snapshot\Factory'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Projection\Factory',
            'BoundedContext\Laravel\Illuminate\Projection\Factory'
        );

        $projection_types = Config::get('projections');

        if(is_null($projection_types))
        {
            return;
        }
        
        foreach($projection_types as $projection_type)
        {
            foreach($projection_type as $projection => $implemented_projection)
            {
                $queryable =
                    '\\' .
                    chop($projection, 'Projection') .
                    "Queryable";

                $implemented_queryable =
                    '\\' .
                    chop($implemented_projection, 'Projection') .
                    "Queryable";

                $this->app->singleton($queryable, $implemented_queryable);
                $this->app->singleton($projection, $implemented_projection);
            }
        }
    }
}
