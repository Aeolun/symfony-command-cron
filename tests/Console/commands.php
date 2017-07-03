<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = new \Symfony\Component\Console\Application();

$command1 = new \Aeolun\Symfony\Console\WritingTestCommand();
$command2 = new \Aeolun\Symfony\Console\WritingTestCommand();
$command2->setName('test:bungled');

$app->add($command1);
$app->add($command2);

$app->run();
