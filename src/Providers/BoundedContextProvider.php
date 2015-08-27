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
        $this->app->bind(
            'Illuminate\Contracts\Bus\Dispatcher',
            'BoundedContext\Laravel\Bus\Dispatcher'
        );

        $this->app->singleton('BoundedContext\Contracts\Map', function($app)
        {
            return new Map(Config::get('events'));
        });

        $this->app->singleton('BoundedContext\Contracts\Log', function($app)
        {
            return new Illuminate\Log(
                $this->app->make('BoundedContext\Laravel\Item\Upgrader'),
                $this->app->make('db'),
                'event_log'
            );
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

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
