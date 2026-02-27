<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Filesystem\Filesystem;

class HookService extends BaseService
{
    private Filesystem $fileSystem;

    public function __construct(protected ConsoleLogger $logger)
    {
        $this->fileSystem = new Filesystem();
        parent::__construct($logger);
    }

    public function create(string $folder): void
    {
        $this->enableCommitMessage($folder);
        $this->enablePreCommit($folder);
    }

    public function remove(): void
    {
        $this->fileSystem->remove([
            self::CORE_REPO_CACHE . '/.git/hooks/pre-commit',
            self::CORE_REPO_CACHE . '/.git/hooks/commit-msg',
        ]);
    }

    private function enableCommitMessage(string $folder): void
    {
        $targetCommitMsg = $folder . '/' . self::CORE_REPO_CACHE . '/.git/hooks/commit-msg';
        $this->fileSystem->copy($folder . '/' . self::CORE_REPO_CACHE . '/Build/git-hooks/commit-msg', $targetCommitMsg);

        if (!is_executable($targetCommitMsg)) {
            $this->fileSystem->chmod($targetCommitMsg, 0755);
        }
    }

    private function enablePreCommit(string $folder): void
    {
        $source = $folder . '/' . self::CORE_REPO_CACHE . '/Build/git-hooks/unix+mac/pre-commit';
        $target = $folder . '/' . self::CORE_REPO_CACHE . '/.git/hooks/pre-commit';
        $this->fileSystem->copy($source, $target);

        if (!is_executable($target)) {
            $this->fileSystem->chmod($target, 0755);
        }
    }
}
