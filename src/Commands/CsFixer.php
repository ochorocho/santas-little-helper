<?php

namespace Ochorocho\SantasLittleHelper\Commands;

use Composer\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'fixer', description: 'Run composer commands')]
class CsFixer extends Command
{
    protected Application $composerApplication;

    protected function configure(): void
    {
        $this->composerApplication = new Application();
        $this->composerApplication->setAutoExit(false);
        $this->composerApplication->setCatchExceptions(false);

        $this
            ->setDescription('Run any composer command - Version ' . $this->composerApplication->getVersion())
            ->addArgument('args', InputArgument::IS_ARRAY, 'All composer arguments')
            ->ignoreValidationErrors();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $args = $input->getArgument('args');

        // If no arguments provided, show the main composer help
        if (empty($args)) {
            $args = [];
        }

        // Use StringInput to pass the raw command line to Composer
        $commandString = implode(' ', array_map('escapeshellarg', $args));
        $composerInput = new StringInput($commandString);

        return $this->composerApplication->run($composerInput, $output);
    }
}
