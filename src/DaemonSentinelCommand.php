<?php
/**
 * @package meisendev/laravel-daemon
 * @author meisendev@163.com
 */

namespace Meisendev\LaravelDaemon;

class DaemonSentinelCommand extends AbstractDaemonSentinelCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentinel:run {config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs as sentinel for a list of daemon commands';
}
