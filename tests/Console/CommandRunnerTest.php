<?php
/**
 * Created by bart
 * Date: 7/22/14
 * Time: 16:35
 *
 */

namespace Console;


use Aeolun\Symfony\Console\CronApplication;
use Aeolun\Symfony\Console\Exception\InvalidCommandException;
use Aeolun\Symfony\Console\TestCommand;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use Symfony\Component\Console\Tests\Output\TestOutput;

class CommandRunnerTest extends \PHPUnit_Framework_TestCase {

    var $testFile = null;

    function __construct() {
        parent::__construct();

        $this->testFile = dirname(__FILE__).'/data/test.cron';
    }

    function testContainsCommands() {
        $runner = new CronApplication($this->testFile);
        $runner->add(new TestCommand());
        $com = new TestCommand();
        $com->setName('test:command');
        $runner->add($com);

        $ex = false;
        try {
            $runner->validateCommands();
        } catch(InvalidCommandException $e) {
            $ex = true;
        }
        $this->assertTrue($ex);

        $com = new TestCommand();
        $com->setName('test:bungled');
        $runner->add($com);

        $this->assertTrue($runner->has('test:task'));
        $this->assertTrue($runner->has('test:command'));
        $this->assertContains('test:task', $runner->getDueCommands('2014-01-01 00:00:00'));
        $this->assertNotContains('test:command', $runner->getDueCommands('2014-01-01 00:34:00'));
        $this->assertContains('test:command', $runner->getDueCommands('2014-01-01 00:00:45'));

        $output = new BufferedOutput();
        $runner->runDueCommands('2014-01-01 00:00:00', $output);
        $result = $output->fetch();
        $this->assertContains("success!\nsuccess!", $result);
    }

}
 