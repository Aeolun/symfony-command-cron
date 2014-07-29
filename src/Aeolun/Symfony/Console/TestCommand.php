<?php
/**
 * Created by bart
 * Date: 7/22/14
 * Time: 17:51
 *
 */

namespace Aeolun\Symfony\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command {

    protected function configure()
    {
        $this->setName('test:task');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('success!');
    }

} 