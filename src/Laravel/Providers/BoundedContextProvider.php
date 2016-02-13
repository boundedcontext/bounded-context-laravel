<?php

namespace BoundedContext\Laravel\Providers;

use BoundedContext\Laravel\Illuminate;
use BoundedContext\Laravel\Illuminate\Log\Log;

use BoundedContext\Map\Map;
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
        /**
         * Events
         */

        $this->app->singleton('EventsMap', function($app)
        {
            $commands = (!Config::get('commands')) ? [] : Config::get('commands');
            $events = (!Config::get('events')) ? [] : Config::get('events');

            $events = array_merge($commands, $events);

            return new Map(
                $events,
                $this->app->make('BoundedContext\Contracts\Generator\Identifier')
            );
        });

        $this->app->bind(
            'BoundedContext\Contracts\Event\Snapshot\Factory',
            'BoundedContext\Laravel\Event\Snapshot\Factory'
        );

        $this->app
            ->when('BoundedContext\Laravel\Event\Snapshot\Factory')
            ->needs('BoundedContext\Map\Map')
            ->give('EventsMap');

        $this->app->bind(
            'BoundedContext\Contracts\Event\Snapshot\Upgrader',
            'BoundedContext\Laravel\Event\Snapshot\Upgrader'
        );

        $this->app
            ->when('BoundedContext\Laravel\Event\Snapshot\Upgrader')
            ->needs('BoundedContext\Map\Map')
            ->give('EventsMap');

        $this->app->bind(
            'BoundedContext\Contracts\Event\Factory',
            'BoundedContext\Laravel\Event\Factory'
        );

        $this->app
            ->when('BoundedContext\Laravel\Event\Factory')
            ->needs('BoundedContext\Map\Map')
            ->give('EventsMap');

        $this->app->bind(
            'BoundedContext\Contracts\Event\Version\Factory',
            'BoundedContext\Laravel\Event\Version\Factory'
        );

        /**
         * Logs
         */

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Stream\Factory',
            'BoundedContext\Laravel\Illuminate\Stream\Factory'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Stream\Builder',
            'BoundedContext\Sourced\Stream\Builder'
        );

        $this->app->singleton('EventLog', function($app)
        {
            return new Log(
                $this->app->make('BoundedContext\Contracts\Event\Snapshot\Factory'),
                $this->app->make('BoundedContext\Contracts\Sourced\Stream\Builder'),
                $this->app->make('db'),
                'event_snapshot_log',
                'event_snapshot_stream'
            );
        });

        $this->app->singleton('CommandLog', function($app)
        {
            return new Log(
                $this->app->make('BoundedContext\Contracts\Event\Snapshot\Factory'),
                $this->app->make('BoundedContext\Contracts\Sourced\Stream\Builder'),
                $this->app->make('db'),
                'command_snapshot_log',
                'command_snapshot_stream'
            );
        });

        /**
         * Aggregates
         */

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Factory',
            'BoundedContext\Sourced\Aggregate\State\Snapshot\Factory'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Repository',
            'BoundedContext\Laravel\Sourced\Aggregate\State\Snapshot\Repository'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Aggregate\State\Factory',
            'BoundedContext\Laravel\Sourced\Aggregate\State\Factory'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Aggregate\Factory',
            'BoundedContext\Laravel\Sourced\Aggregate\Factory'
        );

        $this->app
            ->when('BoundedContext\Sourced\Aggregate\Repository')
            ->needs('BoundedContext\Contracts\Sourced\Log\Log')
            ->give('EventLog');

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Aggregate\Repository',
            'BoundedContext\Sourced\Aggregate\Repository'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Aggregate\Stream\Builder',
            'BoundedContext\Sourced\Aggregate\Stream\Builder'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Sourced\Aggregate\Stream\Factory',
            'BoundedContext\Laravel\Sourced\Aggregate\Stream\Factory'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Business\Invariant\Factory',
            'BoundedContext\Laravel\Business\Invariant\Factory'
        );

        /**
         * Players
         */

        $this->app->bind(
            'BoundedContext\Contracts\Player\Snapshot\Repository',
            'BoundedContext\Laravel\Player\Snapshot\Repository'
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
                    chop($implemented_projection, 'Projection') .
                    "Queryable";

                $this->app
                    ->when($implemented_projection)
                    ->needs('BoundedContext\Contracts\Projection\Queryable')
                    ->give($implemented_queryable);

                $this->app->singleton($projection, $implemented_projection);
                $this->app->singleton($queryable, $implemented_queryable);
            }
        }

        /* Players */
        $this->app->singleton('PlayersMap', function($app)
        {
            $player_environments = Config::get('players');

            if(is_null($player_environments))
            {
                return;
            }

            $players_array = [];
            foreach($player_environments as $player_environment)
            {
                foreach($player_environment as $player_types)
                {
                    foreach($player_types as $id => $player)
                    {
                        $players_array[$id] = $player;
                    }
                }
            }

            return new Map(
                $players_array,
                $this->app->make('BoundedContext\Contracts\Generator\Identifier')
            );
        });

        $this->app
            ->when('BoundedContext\Laravel\Player\Factory')
            ->needs('BoundedContext\Map\Map')
            ->give('PlayersMap');

        $this->app->bind(
            'BoundedContext\Contracts\Player\Factory',
            'BoundedContext\Laravel\Player\Factory'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Player\Repository',
            'BoundedContext\Player\Repository'
        );

        /**
         * General
         */

        $this->app
            ->when('BoundedContext\Laravel\Bus\Dispatcher')
            ->needs('BoundedContext\Contracts\Sourced\Log\Log')
            ->give('CommandLog');

        $this->app->bind(
            'BoundedContext\Contracts\Bus\Dispatcher',
            'BoundedContext\Laravel\Bus\Dispatcher'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Generator\Identifier',
            'BoundedContext\Laravel\Generator\Uuid'
        );

        $this->app->bind(
            'BoundedContext\Contracts\ValueObject\Identifier',
            'BoundedContext\Laravel\ValueObject\Uuid'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Generator\DateTime',
            'BoundedContext\Laravel\Generator\DateTime'
        );

        $this->app->bind(
            'BoundedContext\Contracts\ValueObject\DateTime',
            'BoundedContext\Laravel\ValueObject\DateTime'
        );

        $this->app->bind(
            'BoundedContext\Contracts\Projection\Factory',
            'BoundedContext\Laravel\Illuminate\Projection\Factory'
        );
    }
}
