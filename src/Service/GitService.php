<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class GitService extends BaseService
{
    private PathService $pathService;
    private Filesystem $fileSystem;

    private string $git;

    public function __construct(protected ConsoleLogger $logger, private readonly string $repository, private readonly string $targetFolder, private readonly bool $noInteraction)
    {
        $this->git = (new ExecutableFinder())->find('git');
        $this->pathService = new PathService();
        $this->fileSystem = new Filesystem();

        parent::__construct($logger);
    }

    /**
     * @param array<string> $userData
     * @return void
     */
    public function setGitConfig(array $userData): void
    {
        $pushUrl = 'ssh://' . $userData['username'] . '@' . $this->reviewServer;
        $this->setGitConfigValue('remote.origin.pushurl', $pushUrl);
        $this->setGitConfigValue('user.name', $userData['display_name'] ?? $userData['name'] ?? $userData['username']);
        $this->setGitConfigValue('user.email', $userData['email']);
    }

    public function setCommitTemplate(string $path): void
    {
        $absolutePath = realpath($path);
        if($absolutePath === false) {
            // Resolve home directory
            if(str_starts_with($path, '~')) {
                $absolutePath = $this->pathService->getHomeDirectory() . substr($path, 1);
            }

            $this->fileSystem->mkdir(dirname($absolutePath));
        }

        $this->setGitConfigValue('commit.template', $absolutePath);
    }

    public function createCommitTemplate(string $path): void
    {
        $content = <<<EOF
[BUGFIX|TASK|FEATURE|DOCS]

Resolves: #
Releases: main
EOF;

        try {
            $absolutePath = realpath($path);
            if($absolutePath === false) {
                // Resolve home directory
                if(str_starts_with($path, '~')) {
                    $absolutePath = $this->pathService->getHomeDirectory() . substr($path, 1);
                }

                $this->fileSystem->mkdir(dirname($absolutePath));
            }
            $this->fileSystem->dumpFile($path, $content);
        } catch (IOException $e) {
            $this->logger->error('<error>Failed to create the commit template ' . $path . '</error>');
        }
    }

    /**
     * Clone the TYPO3 git repository and create a copy
     * in the config directory to speed up future repository downloads.
     *
     * This means:
     *  - First clone will download the entire repository
     *  - All following clones will just copy and update the
     *    existing repository unless $ignoreCache is not set.
     *
     * @param string $url The repositor to clone from
     * @param bool $ignoreCache Ignore the copy created in the config directory
     */
    public function cloneRepository(string $url, bool $ignoreCache = false): void
    {
        $this->fileSystem->mkdir([$this->targetFolder]);
        $repoTargetPath = $ignoreCache ? $this->targetFolder . '/' . self::CORE_REPO_CACHE : $this->pathService->getConfigFolder() . '/' . self::CORE_REPO_CACHE;

        if (!$this->fileSystem->exists($repoTargetPath)) {
            $process = $this->cloneRepositoryToProjectFolder($url, $repoTargetPath);

            if (!$process->isSuccessful()) {
                $this->logger->warning('<warning>Could not download git repository ' . $url . ' </warning>');
            }
        }

        if (!$ignoreCache) {
            // Use the existing local repository and clone from there
            $this->pull($repoTargetPath);

            if (!$this->fileSystem->exists($this->targetFolder . '/' . self::CORE_REPO_CACHE)) {
                $this->logger->notice('Using local clone for core setup');
                $process = $this->cloneRepositoryToProjectFolder($repoTargetPath, $this->targetFolder . '/' . self::CORE_REPO_CACHE);

                if ($process->isSuccessful()) {
                    $this->setGitConfigValue('remote.origin.url', $this->repository);
                }
            }
        }
    }

    public function checkoutBranch(string $branch): int
    {
        if (empty($branch)) {
            $this->logger->error(('<warning>No branch name given</warning>'));
            return Command::FAILURE;
        }

        $process = new Process([$this->git, 'checkout', $branch], $this->targetFolder . '/' . self::CORE_REPO_CACHE);
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->run();

        $this->logger->info('<info>Checking out branch "' . $branch . '"!</info>');
        if (!$process->isSuccessful()) {
            $this->logger->error('<warning>Could not checkout branch ' . $branch . ' </warning>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function pull(string $folder): void
    {
        $process = new Process([$this->git, 'pull'], $folder);
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->run();
    }

    private function setGitConfigValue(string $config, string $value): void
    {
        $process = new Process([$this->git, 'config', $config, $value], $this->targetFolder . '/' . self::CORE_REPO_CACHE);
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Failed to set git config value for "' . $config . '"');
        }
    }

    /**
     * @param string $url
     * @param string $repoTargetPath
     * @return Process
     */
    public function cloneRepositoryToProjectFolder(string $url, string $repoTargetPath): Process
    {
        $process = new Process([$this->git, 'clone', $url, $repoTargetPath]);
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $this->logger->notice('<info>Cloning TYPO3 repository. This may take a while depending on your internet connection!</info>');
        $process->run();
        return $process;
    }
}
