<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Commands;

use Composer\InstalledVersions;
use Phar;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'phpstan', description: 'Run PHPStan commands')]
class PhpStan extends Command
{
    protected function configure(): void
    {
        $version = InstalledVersions::getPrettyVersion('phpstan/phpstan') ?? 'unknown';
        $this
            ->setDescription('Run any PHPStan command - Version ' . $version)
            ->addArgument('args', InputArgument::IS_ARRAY, 'All PHPStan arguments')
            ->ignoreValidationErrors();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get arguments from Symfony Console input instead of raw $_SERVER['argv']
        $phpstanArgs = $input->getArgument('args') ?? [];

        // If no arguments provided, default to help
        if (empty($phpstanArgs)) {
            $phpstanArgs = ['--help'];
        }

        // Find the PHPStan binary
        $phpstanPath = $this->findPhpStanBinary();
        if (!$phpstanPath) {
            $output->writeln('<error>PHPStan binary not found. Please ensure phpstan/phpstan is installed via Composer.</error>');
            return Command::FAILURE;
        }

        // Add --ansi flag if terminal supports colors and it's not already specified
        // But only if we have other arguments (PHPStan treats standalone --ansi as a file path)
        if ($this->supportsColors() && !in_array('--ansi', $phpstanArgs) && !in_array('--no-ansi', $phpstanArgs) && count($phpstanArgs) > 1) {
            $phpstanArgs[] = '--ansi';
        }

        // Execute PHPStan with all arguments passed through
        $output->writeln("<info>Running PHPStan: {$phpstanPath}</info>", OutputInterface::VERBOSITY_VERBOSE);
        
        if (str_starts_with($phpstanPath, 'phar://')) {
            // For PHAR files, execute through PHP
            $command = 'php ' . escapeshellarg($phpstanPath);
            foreach ($phpstanArgs as $arg) {
                $command .= ' ' . escapeshellarg($arg);
            }
        } else {
            // For regular binaries, execute directly
            $command = escapeshellarg($phpstanPath);
            foreach ($phpstanArgs as $arg) {
                $command .= ' ' . escapeshellarg($arg);
            }
        }
        
        passthru($command, $exitCode);
        
        return $exitCode;
    }

    private function findPhpStanBinary(): ?string
    {
        if (Phar::running()) {
            // Code running from within PHAR - use the non-PHAR PHPStan binary first
            // (PHAR-in-PHAR execution has limitations)
            $pharPath = Phar::running();
            $phpstanBin = $pharPath . "/vendor/phpstan/phpstan/phpstan";
            if (file_exists($phpstanBin)) {
                return $phpstanBin;
            }
            
            // Fallback to PHPStan PHAR (may not work due to nested PHAR limitations)
            $phpstanPhar = $pharPath . "/vendor/phpstan/phpstan/phpstan.phar";
            if (file_exists($phpstanPhar)) {
                return $phpstanPhar;
            }
        } else {
            // Code running normally - look for external vendor/bin/phpstan
            $vendorBin = getcwd() . '/vendor/bin/phpstan';
            if (file_exists($vendorBin)) {
                return $vendorBin;
            }
        }


        // Try global phpstan as final fallback
        $globalPath = trim((string) shell_exec('which phpstan 2>/dev/null'));
        if ($globalPath && file_exists($globalPath)) {
            return $globalPath;
        }

        return null;
    }

    private function supportsColors(): bool
    {
        // Check if we're not running in a piped environment and terminal supports colors
        if (getenv('NO_COLOR') !== false) {
            return false;
        }
        
        if (getenv('FORCE_COLOR') !== false) {
            return true;
        }
        
        // Check if we have a TERM variable that indicates color support
        $term = getenv('TERM');
        if ($term && (
            strpos($term, 'color') !== false ||
            strpos($term, 'xterm') !== false ||
            strpos($term, 'screen') !== false ||
            $term === 'dumb'
        )) {
            return $term !== 'dumb';
        }
        
        // Check if we have COLORTERM
        if (getenv('COLORTERM')) {
            return true;
        }
        
        // Default to true for common terminals
        return true;
    }
}
