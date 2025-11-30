<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Filesystem\Filesystem;

class HookService extends BaseService
{
    public function __construct(protected ConsoleLogger $logger)
    {
        parent::__construct($logger);
    }

    public function create(string $folder): void
    {
        $this->enableCommitMessage($folder);
        $this->enablePreCommit($folder);
    }

    public function remove(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove([
            $this->coreDevFolder . '/.git/hooks/pre-commit',
            $this->coreDevFolder . '/.git/hooks/commit-msg',
        ]);
    }

    private function enableCommitMessage(string $folder): void
    {
        $filesystem = new Filesystem();

        $targetCommitMsg = $folder . '/' . self::CORE_REPO_CACHE . '/.git/hooks/commit-msg';
        $filesystem->copy($folder . '/' . self::CORE_REPO_CACHE . '/Build/git-hooks/commit-msg', $targetCommitMsg);

        if (!is_executable($targetCommitMsg)) {
            $filesystem->chmod($targetCommitMsg, 0755);
        }
    }

    private function enablePreCommit(string $folder): void
    {
        $filesystem = new Filesystem();
        $source = $folder . '/' . self::CORE_REPO_CACHE . '/Build/git-hooks/unix+mac/pre-commit';
        $target = $folder . '/' . self::CORE_REPO_CACHE . '/.git/hooks/pre-commit';
        $filesystem->copy($source, $target);

        if (!is_executable($target)) {
            $filesystem->chmod($target, 0755);
        }
    }
}
