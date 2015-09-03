<?php

return [

    'database' => [
        'tables' => [
            'command_log'   => 'command_log',
            'command_stream'=> 'command_stream',
            'event_log'     => 'event_log',
            'event_stream'  => 'event_stream',
            'projectors'    => 'projectors',
            'workflows'     => 'workflows',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Projections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'projections' => [

        'core' => [
            \BoundedContext\Projection\AggregateCollections\Projection::class =>
                \BoundedContext\Laravel\Illuminate\Projection\AggregateCollections::class,
        ],

        'domain' => [

        ],

        'app' => [

        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflows
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'workflows' => [

        'app' => [
            /*
            \App\Workflow\User::class,
            \App\Workflow\Another::class,
            */
        ],

        'domain' => [
            /*
            \Domain\Test\Workflow\User::class,
            \Domain\Test\Workflow\Another::class,
            */
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Commands
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'commands' => [
        /*
        '5225203f-3ff0-44aa-9142-4da277e6c009' =>
            \Domain\Test\Aggregate\User\Command\Create::class,
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'events' => [
        /*
        'cfd9ef79-2cf3-4ee6-805f-619f72352921' =>
            \Domain\Test\Aggregate\User\Event\Created::class,
        */
    ],
];