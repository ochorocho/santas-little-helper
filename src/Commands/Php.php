<?php

namespace Ochorocho\SantasLittleHelper\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'php', description: 'Run PHP scripts using the embedded PHP interpreter')]
class Php extends Command
{
    protected function configure()
    {
        $this->addArgument('script', InputArgument::REQUIRED, 'PHP script file to execute')
            ->addArgument('args', InputArgument::IS_ARRAY, 'Arguments to pass to the PHP script');
            
        $this->setHelp('This command runs PHP scripts using the embedded PHP interpreter. Usage: php script.php [args...]');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $script = $input->getArgument('script');
        $args = $input->getArgument('args') ?? [];

        // Check if script file exists
        if (!file_exists($script)) {
            $output->writeln("<error>PHP script file not found: {$script}</error>");
            return Command::FAILURE;
        }

        try {
            // Read the PHP script content
            $scriptContent = file_get_contents($script);
            if ($scriptContent === false) {
                $output->writeln("<error>Failed to read script: {$script}</error>");
                return Command::FAILURE;
            }

            // Set up $_SERVER['argv'] for the script
            $originalArgv = $_SERVER['argv'] ?? [];
            $_SERVER['argv'] = array_merge([$script], $args);
            $_SERVER['argc'] = count($_SERVER['argv']);

            // Set up output buffering to capture script output
            ob_start();
            
            // Execute the PHP script in the current process
            $result = $this->executePhpScript($scriptContent, $script);
            
            // Get any output from the script
            $scriptOutput = ob_get_clean();
            
            // Restore original argv
            $_SERVER['argv'] = $originalArgv;
            $_SERVER['argc'] = count($originalArgv);
            
            // Output the script's output
            if ($scriptOutput) {
                $output->write($scriptOutput);
            }
            
            return $result;
            
        } catch (\Throwable $e) {
            $output->writeln("<error>Error executing script: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }

    private function executePhpScript(string $scriptContent, string $scriptPath): int
    {
        // Remove shebang line if present
        if (str_starts_with($scriptContent, '#!')) {
            $firstNewline = strpos($scriptContent, "\n");
            if ($firstNewline !== false) {
                $scriptContent = substr($scriptContent, $firstNewline + 1);
            }
        }
        
        // Remove opening PHP tag if present
        if (str_starts_with($scriptContent, '<?php')) {
            $scriptContent = substr($scriptContent, 5);
        }

        // Change working directory to script's directory
        $originalCwd = getcwd();
        $scriptDir = dirname(realpath($scriptPath));
        chdir($scriptDir);

        try {
            // Execute the script code
            $result = eval($scriptContent);
            
            // If the script returned an exit code, use it
            if (is_int($result)) {
                return $result;
            }
            
            return Command::SUCCESS;
            
        } catch (\ParseError $e) {
            throw new \RuntimeException("Parse error in {$scriptPath}: " . $e->getMessage());
        } catch (\Error $e) {
            throw new \RuntimeException("Fatal error in {$scriptPath}: " . $e->getMessage());
        } finally {
            chdir($originalCwd);
        }
    }
}