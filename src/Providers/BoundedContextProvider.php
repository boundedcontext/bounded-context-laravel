<?php

namespace BoundedContext\Laravel\Providers;

use BoundedContext\Laravel\Log\InMemoryLog;
use BoundedContext\Map\Map;

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

        $projection_types = Config::get('projections');
        foreach($projection_types as $projection_type)
        {
            foreach($projection_type as $projection => $implemented_projection)
            {
                $this->app->bind($projection, $implemented_projection);
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
