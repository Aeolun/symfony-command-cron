<?php
/**
 * Created by bart
 * Date: 7/22/14
 * Time: 16:23
 *
 */

namespace Aeolun\Symfony\Console;

use Aeolun\Symfony\Console\Exception\FileNotReadableException;
use Aeolun\Symfony\Console\Exception\InvalidCommandException;
use Cron\CronExpression;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CronApplication {

    var $expressions = [];
    var $application = null;

    function __construct($cronFile, $name='UNKNOWN', $version='UNKNOWN') {
        if (!file_exists($cronFile) || !is_readable($cronFile)) throw new FileNotReadableException("File ".$cronFile." is unreadable.");

        $data = file_get_contents($cronFile);
        $lines = explode("\n", $data);
        foreach($lines as $line) {
            $commandStart = strrpos($line, ' ');
            $expression = substr($line, 0, $commandStart);
            $this->expressions[trim(substr($line, $commandStart))] = $expression;
        }

        $this->application = new Application($name, $version);
    }

    function add(Command $command) {
        return $this->application->add($command);
    }

    function addCommands($commands) {
        $this->application->addCommands($commands);
    }

    function has($command) {
        return $this->application->has($command);
    }

    function validateCommands() {
        foreach($this->expressions as $command=>$expression) {
            if (!$this->has($command)) throw new InvalidCommandException("Command '".$command."' does not exist in application.");
        }
    }

    function getDueCommands($specificTime=null) {
        $this->validateCommands();

        $commands = [];
        foreach($this->expressions as $command=>$expression) {
            $ex = CronExpression::factory($expression);

            if ($ex->isDue($specificTime)) {
                $commands[] = $command;
            }
        }
        return $commands;
    }

    function runDueCommands($specificTime=null, OutputInterface $output = null) {
        $commands = $this->getDueCommands($specificTime);

        if ($output == null) $output = new ConsoleOutput();

        foreach($commands as $command) {
            $input = new StringInput($command);
            $exitCode = $this->application->doRun($input, $output);
        }
    }

    /**
     * This function will allow your cron script to run commands in parallel and wait until they all finish
     *
     * @param null $specificTime
     * @param OutputInterface $output
     */
    function runDueCommandsParallel($specificTime=null, OutputInterface $output = null) {
        global $argv;

        if (count($argv) > 1) {
            if ($output == null) $output = new ConsoleOutput();

            $this->application->run(null, $output);
        } else {
            $commands = $this->getDueCommands($specificTime);

            if ($output == null) $output = new ConsoleOutput();

            $processes = [];
            foreach($commands as $command) {
                $output->writeln("Starting ".$argv[0].' '.$command);
                $process = new Process($argv[0].' '.$command);
                $process->start();

                $processes[] = $process;
            }
            while(true) {
                $close = true;
                foreach($processes as $process) {
                    /** @var Process $process */
                    if ($process->isRunning()) {
                        $output->writeln($process->getCommandLine().' still running under '.$process->getPid());
                        $close = false;
                    }
                }
                if ($close) break;
                sleep(1);
            }
        }
    }

} 