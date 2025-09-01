<?php

namespace Ochorocho\SantasLittleHelper\Commands;

use Composer\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'composer', description: 'Run composer commands')]
class Composer extends Command
{
    protected function configure()
    {
        $this
            ->setDescription('Wrapper for Composer commands')
            ->addArgument('args', InputArgument::IS_ARRAY, 'All composer arguments')
            ->ignoreValidationErrors();
            
        $this->setHelp('This command allows you to run Composer commands like install, update, require, etc. If no subcommand is provided, it will show the list of available commands.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $args = $input->getArgument('args');

        // If no arguments provided, default to 'list'
        if (empty($args)) {
            $args = ['list'];
        }

        // Create Composer application
        $composerApp = new Application();
        $composerApp->setAutoExit(false);
        $composerApp->setCatchExceptions(false);

        try {
            // Use StringInput to pass the raw command line to Composer
            $commandString = implode(' ', array_map('escapeshellarg', $args));
            $composerInput = new StringInput($commandString);
            
            return $composerApp->run($composerInput, $output);
        } catch (\Exception $e) {
            $output->writeln('<error>Composer command failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
