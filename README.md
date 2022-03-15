# laravel-daemon

**Runs as sentinel for a list of daemon commands**

In practice, a project can have a number of commands to be executed in a pre-determined schedule. Some commands need to
be executed at a given interval, some need to run more than one instance in parallel, some need to automatically send
alert when execution fails, and so on. This library provides a very useful feature called the Daemon Sentinel just to
solve this problem.

This library based on oasis/slimapp and custom-made for laravel framework.

## Quick Start

In your laravel project:

```shell
composer require meisendev/laravel-daemon
```

Add sentinel command to `App\Console\Kernel` like this:

```php
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        TestDaemon1::class,
        \Meisendev\LaravelDaemon\DaemonSentinelCommand::class,
    ];
}
```

Add config file(e.g. `meisendev.php`) to config folder.

`meisendev.php` setup like this:

```php
<?php

return [
    'test:daemon:1' => [
        'name' => 'test:daemon:1',//regular Artisan command signature, note: the name without args
        'once' => false,//run only once? if not, command will restart upon previous execution ends
        'parallel' => 5,//how many parallel
        'args' => [//regular Artisan command args like: 'test:daemon:1 {arg1} {arg2}'
            'arg1' => 'test1',
            'arg2' => 'test11'
        ],
        'interval' => 3,//minimum number of seconds between last end and next start
        'alert' => true
    ],
    'test:daemon:2' => [
        'name' => 'test:daemon:2',
        'once' => true,
        'parallel' => 8,
        'args' => [
            'arg1' => 'test2'
        ],
        'frequency' => '3',//minimum seconds between two start
        'alert' => true
    ]
];
```

Run sentinel command like this:

```shell
php artisan sentinel:run meisendev
```

`meisendev` is the config file name

> Note: You'd better run above command with screen!

