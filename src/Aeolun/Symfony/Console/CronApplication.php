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
            $this->expressions[$expression] = trim(substr($line, $commandStart));
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
        foreach($this->expressions as $expression=>$command) {
            if (!$this->has($command)) throw new InvalidCommandException("Command '".$command."' does not exist in application.");
        }
    }

    function getDueCommands($specificTime=null) {
        $this->validateCommands();

        $commands = [];
        foreach($this->expressions as $expression=>$command) {
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

} 