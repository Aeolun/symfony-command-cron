<?php

namespace Aeolun\Symfony\Console;

use Aeolun\Symfony\Console\Exception\FileNotReadableException;
use Aeolun\Symfony\Console\Exception\InvalidCommandException;
use Cron\CronExpression;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CronApplication
{
    var $expressions = [];
    var $application = null;
    private $parallelScript = null;
    private $phpBin = null;

    function __construct($cronFile, $parallelScript = null, $name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        if ( ! file_exists($cronFile) || ! is_readable($cronFile)) {
            throw new FileNotReadableException('File ' . $cronFile . ' is unreadable.');
        }

        $data = file_get_contents($cronFile);
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            if (trim($line) == '') {
                continue;
            }

            preg_match('/(([\*0-9\/]+\s+){5})(.+)/', $line, $matches);
            $this->expressions[trim($matches[3])] = trim($matches[1]);
        }

        $this->application = new Application($name, $version);
        $this->parallelScript = $parallelScript;
        $this->phpBin = PHP_BINARY;
    }

    function setPhpBin($phpBin)
    {
        $this->phpBin = $phpBin;
    }

    function add(Command $command)
    {
        return $this->application->add($command);
    }

    function addCommands($commands)
    {
        $this->application->addCommands($commands);
    }

    function has($command)
    {
        return $this->application->has($command);
    }

    function getCommandName($command)
    {
        if (strpos($command, ' ') !== false) {
            return trim(substr($command, 0, strpos($command, ' ')));
        }

        return $command;
    }

    function validateCommands()
    {
        foreach ($this->expressions as $command => $expression) {
            if ( ! $this->has(
                $this->getCommandName($command)
            )
            ) {
                throw new InvalidCommandException("Command '" . $command . "' does not exist in application.");
            }
        }
    }

    function getDueCommands($specificTime = null)
    {
        $this->validateCommands();

        $commands = [];
        foreach ($this->expressions as $command => $expression) {
            $ex = CronExpression::factory($expression);

            if ($ex->isDue($specificTime)) {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    function runDueCommands($specificTime = null, OutputInterface $output = null)
    {
        $commands = $this->getDueCommands($specificTime);

        if ($output == null) {
            $output = new ConsoleOutput();
        }

        foreach ($commands as $command) {
            $command = 'app ' . $command; //first argument is removed in ArgvInput
            $input = new ArgvInput(explode(' ', $command));
            $exitCode = $this->application->doRun($input, $output);
        }
    }

    /**
     * This function will allow your cron script to run commands in parallel and wait until they all finish
     *
     * @param null            $specificTime
     * @param OutputInterface $output
     */
    function runDueCommandsParallel($specificTime = null, OutputInterface $output = null)
    {
        if ( ! $this->parallelScript) {
            throw new \RuntimeException('Cannot run commands in parallel without defining a parallel command script.');
        }

        $commands = $this->getDueCommands($specificTime);

        if ($output == null) {
            $output = new ConsoleOutput();
        }

        $processes = [];
        foreach ($commands as $command) {
            $process = new Process($this->phpBin . ' ' . $this->parallelScript . ' ' . $command);
            $output->writeln('Starting ' . $process->getCommandLine());

            $process->start();

            $processes[] = $process;
        }
        while (true) {
            $close = true;
            foreach ($processes as $process) {
                /** @var Process $process */
                if ($process->isRunning()) {
                    $output->writeln($process->getCommandLine() . ' still running as pid ' . $process->getPid());
                    $close = false;
                } else {
                    $output->write($process->getErrorOutput() . "\n" . $process->getOutput());
                }
            }
            if ($close) {
                break;
            }
            sleep(60);
        }

        return true;
    }
}
