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

    public function __construct(private readonly SymfonyStyle $io, private readonly OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($this->output);
    }

    public function enable(bool $force = false): void
    {
        $questions = [
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

        $force = (bool)($force ?: getenv('SLH_HOOK_FORCE_CREATE') ?: false);
        foreach ($questions as $question) {
            if ($force) {
                $answer = true;
            } else {
                $answer = $this->io->confirm($question['message'], $question['default']);
            }

            if ($answer) {
                $method = $question['method'];
                $this->$method();
            }
        }
    }

    public function remove(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove([
            $this->coreDevFolder . '/.git/hooks/pre-commit',
            $this->coreDevFolder . '/.git/hooks/commit-msg',
        ]);
    }

    private function enableCommitMessage(): void
    {
        $filesystem = new Filesystem();

        try {
            $targetCommitMsg = $this->coreDevFolder . '/.git/hooks/commit-msg';
            $filesystem->copy($this->coreDevFolder . '/Build/git-hooks/commit-msg', $targetCommitMsg);

            if (!is_executable($targetCommitMsg)) {
                $filesystem->chmod($targetCommitMsg, 0755);
            }
        } catch (IOException $e) {
            $this->logger->error('Exception:enableCommitMessageHook:' . $e->getMessage());
        }
    }

    private function enablePreCommit(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return;
        }

        $filesystem = new Filesystem();
        try {
            $targetPreCommit = $this->coreDevFolder . '/.git/hooks/pre-commit';
            $filesystem->copy($this->coreDevFolder . '/Build/git-hooks/unix+mac/pre-commit', $targetPreCommit);

            if (!is_executable($targetPreCommit)) {
                $filesystem->chmod($targetPreCommit, 0755);
            }
        } catch (IOException $e) {
            $this->logger->warning('Exception:enablePreCommitHook:' . $e->getMessage());
        }
    }
}
