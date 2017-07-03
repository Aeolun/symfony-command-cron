<?php

namespace Aeolun\Symfony\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WritingTestCommand extends Command
{
    protected function configure()
    {
        $this->setName('test:task')->addArgument('file', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');
        $output->writeln('writing to ' . $file);
        file_put_contents($file, "success!\n", FILE_APPEND);
    }
}
