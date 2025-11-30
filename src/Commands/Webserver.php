<?php

namespace Ochorocho\SantasLittleHelper\Commands;

use Ochorocho\SantasLittleHelper\Service\PathService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'webserver', description: 'Run the FrankenPHP Webserver')]
class Webserver extends Command
{
    protected PathService $pathService;

    public function __construct(?string $name = null)
    {
        $this->pathService = new PathService();
        parent::__construct($name);
    }
    protected function configure(): void
    {
        // @todo: Well, we could just pass the options/args through
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Caddyfile - Webserver configuration', './Caddyfile')
            ->addOption('envfile', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Environment file(s) to load', ['.env'])
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch config file for changes and reload it automatically')
            ->addOption('environ', 'e', InputOption::VALUE_NONE, 'Print environment');
        $this->setHelp('This command runs the FrankenPHP Webserver');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        // todo: sane defaults for .env and Caddyfile
        //       only write files if they do not exist?!?!
        $phpBinary = $this->pathService->getConfigFolder() . '/bin/frankenphp';
        $this->pathService->pharExtractFileToConfigBin('bin/frankenphp');
        $configPath = realpath($input->getOption('config'));
        if (!$configPath) {
            $logger->error('Config file not found: ' . $input->getOption('config'));
            return Command::FAILURE;
        }

        $args = [];
        $args[] = '--config=' . $configPath;

        if ($input->getOption('watch')) {
            $args[] = '--watch';
        }

        if ($input->getOption('environ')) {
            $args[] = '--environ';
        }

        $envOptionArray = $input->getOption('envfile');
        foreach ($envOptionArray as $envPath) {
            if ($path = realpath($envPath)) {
                $args[] = '--envfile=' . $path;
            }
        }

        $process = new Process([$phpBinary, 'run', ...$args]);
        $process->setTty(true);
        $process->setTimeout(null);
        $output->writeln(['<comment>Running command:</comment>', $process->getCommandLine(), '']);
        $process->start();

        foreach ($process->getIterator() as $data) {
            $output->write($data);
        }

        return Command::SUCCESS;
    }
}
