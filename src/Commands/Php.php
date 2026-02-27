<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Commands;

use Ochorocho\SantasLittleHelper\Factory\ConsoleLoggerFactory;
use Ochorocho\SantasLittleHelper\Service\PathService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'php', description: 'Run PHP scripts using the embedded PHP interpreter')]
class Php extends Command
{
    protected PathService $pathService;

    public function __construct(?string $name = null)
    {
        $this->pathService = new PathService();
        parent::__construct($name);
    }
    protected function configure(): void
    {
        $this->addArgument('script', InputArgument::REQUIRED, 'PHP file to execute')
            ->addArgument('args', InputArgument::IS_ARRAY, 'Arguments to pass to the PHP script');
        $this->setHelp('This command runs PHP scripts using the embedded PHP interpreter. Usage: php script.php [args...]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = ConsoleLoggerFactory::create($output);
        $phpBinary = $this->pathService->getFrankenPhpBinary();
        $script = realpath($input->getArgument('script'));
        if (!$script) {
            $logger->error('Failed to execute file ' . $input->getArgument('script') . ', because it was not found.');
            return Command::FAILURE;
        }

        $args = $input->getArgument('args') ?? [];
        // @todo: Verify if this is working for args and options!
        $process = new Process([$phpBinary, 'php-cli', $script, ...$args]);
        $process->setTty(Process::isTtySupported());
        $output->writeln(['<comment>Running command:</comment>', $process->getCommandLine(), '']);
        $process->setTimeout(null);
        $process->start();

        foreach ($process->getIterator() as $data) {
            $output->write($data);
        }

        return $process->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }
}
