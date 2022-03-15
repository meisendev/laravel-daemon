<?php
/**
 * @package meisendev/laravel-daemon
 * @author meisendev@163.com
 */

namespace Meisendev\LaravelDaemon;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDaemonSentinelCommand extends Command
{
    const EXIT_CODE_OK = 0;
    const EXIT_CODE_COMMON_ERROR = 0xff;
    const SENTINEL_PID_FILE = 'sentinel.pid';

    /**
     * @var CommandRunner[]
     */
    protected $runningProcesses = [];

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->recordPid();

        $file = $input->getArgument('config');
        $processed = config($file);

        $this->runningProcesses = [];
        foreach ($processed as $command) {
            $parallel = $command['parallel'];
            if ($parallel != intval($parallel)) {
                throw new InvalidArgumentException("parallel value is not an integer! <$parallel>");
            }

            for ($i = 0; $i < $parallel; ++$i) {
                $runner = new CommandRunner($i, $command);
                $pid = $runner->run();

                $this->runningProcesses[$pid] = $runner;
            }
        }

        $this->waitForBackgroundProcesses();

        return 0;
    }

    protected function recordPid()
    {
        $basePath = base_path();
        $pidFile = $basePath . DIRECTORY_SEPARATOR . AbstractDaemonSentinelCommand::SENTINEL_PID_FILE;

        $fp = fopen($pidFile, 'w');
        fwrite($fp, getmypid());
        fclose($fp);
    }

    protected function waitForBackgroundProcesses()
    {
        while (true) {
            pcntl_signal_dispatch();

            $status = 0;
            $pid = pcntl_waitpid(-1, $status, WNOHANG);

            if ($pid == 0) {
                $jumpStarted = [];
                foreach ($this->runningProcesses as $runner) {
                    if ($runner->shouldStartNextRunWhenNotFinished()) {
                        $earlyRunner = $runner->cloneEarlyRunner();
                        $earlyRunnerPid = $earlyRunner->run();
                        $jumpStarted[$earlyRunnerPid] = $earlyRunner;
                    }
                }
                $this->runningProcesses = $this->runningProcesses + $jumpStarted;
                usleep(200 * 1000);
            } else if ($pid > 0) {
                if (!isset($this->runningProcesses[$pid])
                    || !(($runner = $this->runningProcesses[$pid]) instanceof CommandRunner)
                ) {
                    throw new LogicException(sprintf('cannot find command runner for process pid = %d', $pid));
                }
                unset($this->runningProcesses[$pid]);
                $runner->onProcessExit();
                $newPid = $runner->run();
                if ($newPid > 0) {
                    $this->runningProcesses[$newPid] = $runner;
                }
            } else {
                $errno = pcntl_get_last_error();
                if ($errno == PCNTL_ECHILD) {
                    break;
                } else {
                    throw new RuntimeException('error waiting for process, error = ' . pcntl_strerror($errno));
                }
            }
        }
    }
}
