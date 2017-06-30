# Symfony Command Cron

[![build success](https://travis-ci.org/Aeolun/symfony-command-cron.svg?branch=master)](https://travis-ci.org/Aeolun/symfony-command-cron)
[![code quality](https://scrutinizer-ci.com/g/Aeolun/symfony-command-cron/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Aeolun/symfony-command-cron)
[![Latest Stable Version](https://poser.pugx.org/aeolun/symfony-command-cron/v/stable.svg)](https://packagist.org/packages/aeolun/symfony-command-cron) 
[![Total Downloads](https://poser.pugx.org/aeolun/symfony-command-cron/downloads.svg)](https://packagist.org/packages/aeolun/symfony-command-cron) 
[![Latest Unstable Version](https://poser.pugx.org/aeolun/symfony-command-cron/v/unstable.svg)](https://packagist.org/packages/aeolun/symfony-command-cron) 
[![License](https://poser.pugx.org/aeolun/symfony-command-cron/license.svg)](https://packagist.org/packages/aeolun/symfony-command-cron)

This package functions almost the same as a normal Symfony console application, except that the commands to run are not determined manually (e.g. command line parameter), but by a file with a cron syntax that can be saved with your application in the repository.

The main reason for creating it is the need for some mechanism to easily update and track changes to cron scripts over multiple servers.

Requirements
------------

- PHP >=5.4

Usage
-----
 
Your code would look something like this (not much different from a normaly Symfony Console application, except the constructor takes a cron file to work with.

    $application = new CronApplication(APPPATH.'config/background.cron');
    $application->add(new \Vendor\Command\DoStuff());
    $application->add(new \Vendor\Command\DoImportantStuff());
    $application->runDueCommands();

The cron file syntax is simple, and follows the accepted pattern. Complete description of the patterns available can be found here: https://github.com/mtdowling/cron-expression

    * * * 4 * stuff:do
    */3 * * * * stuff:important:do

Install
-------

Symfony Command Cron can be installed through composer:

    composer require aeolun/symfony-command-cron:^0.1
