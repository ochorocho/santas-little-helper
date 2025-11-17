<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Ochorocho\SantasLittleHelper\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class HookService extends BaseService
{
    protected ConsoleLogger $logger;

    public function __construct(private readonly OutputInterface $output, private readonly string $targetFolder)
    {
        $this->logger = new ConsoleLogger($this->output);
    }

    public function getHookQuestions(): array
    {
        return [
            [
                'method' => 'enableCommitMessage',
                'message' => 'Setup Commit Message Hook?',
                'default' => true
            ],
            [
                'method' => 'enablePreCommit',
                'message' => 'Setup Pre Commit Hook?',
                'default' => true
            ],
        ];
    }

    public function remove(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove([
            $this->coreDevFolder . '/.git/hooks/pre-commit',
            $this->coreDevFolder . '/.git/hooks/commit-msg',
        ]);
    }

    public function enableCommitMessage(): void
    {
        $filesystem = new Filesystem();

        try {
            $targetCommitMsg = $this->targetFolder . '/' . self::CORE_REPO_CACHE . '/.git/hooks/commit-msg';
            $filesystem->copy($this->targetFolder . '/' . self::CORE_REPO_CACHE . '/Build/git-hooks/commit-msg', $targetCommitMsg);

            if (!is_executable($targetCommitMsg)) {
                $filesystem->chmod($targetCommitMsg, 0755);
            }
        } catch (IOException $e) {
            $this->logger->error('Exception:enableCommitMessageHook:' . $e->getMessage());
        }
    }

    public function enablePreCommit(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return;
        }

        $filesystem = new Filesystem();
        try {
            $targetPreCommit = $this->targetFolder . '/' . self::CORE_REPO_CACHE . '/.git/hooks/pre-commit';
            $filesystem->copy($this->targetFolder . '/' . self::CORE_REPO_CACHE . '/Build/git-hooks/unix+mac/pre-commit', $targetPreCommit);

            if (!is_executable($targetPreCommit)) {
                $filesystem->chmod($targetPreCommit, 0755);
            }
        } catch (IOException $e) {
            $this->logger->warning('Exception:enablePreCommitHook:' . $e->getMessage());
        }
    }
}
