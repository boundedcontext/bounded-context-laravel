<?php

return [

    'database' => [
        'tables' => [
            'log'           => 'event_log',
            'stream'        => 'event_stream',
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
            \BoundedContext\Contracts\Projection\AggregateCollections::class =>
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
        '00000000-0000-0000-0000-000000000000' =>
            \Domain\BoundedContext\Aggregate\Event::class,
        */
    ]
];