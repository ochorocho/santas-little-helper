<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Composer\Script\Event;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class CommonService extends BaseService
{
    public function doctor(Event $event): void
    {
        $filesystem = new Filesystem();

        // Test for existing repository
        if ($filesystem->exists($this->coreDevFolder . '/.git')) {
            $event->getIO()->write('<fg=green;options=bold>✔</> Repository exists.');
        } else {
            $event->getIO()->write('<fg=red;options=bold>✘</> TYPO3 Repository not in place, please run "composer tdk:clone"');
        }

        // Test if hooks are set up
        if ($filesystem->exists([
            $this->coreDevFolder . '/.git/hooks/pre-commit',
            $this->coreDevFolder . '/.git/hooks/commit-msg',
        ])) {
            $event->getIO()->write('<fg=green;options=bold>✔</> All hooks are in place.');
        } else {
            $event->getIO()->write('<fg=red;options=bold>✘</> Hooks are missing please run "composer tdk:enable-hooks".');
        }

        // Test git push url
        // $process = new ProcessExecutor();
        $process = new Process(['git', 'config', '--get', 'remote.origin.pushurl'], $this->coreDevFolder);
        $process->setTty(false);
        $process->run();

        preg_match('/^ssh:\/\/(.*)@review\.typo3\.org/', $process->getOutput(), $matches);
        if (!empty($matches)) {
            $event->getIO()->write('<fg=green;options=bold>✔</> Git "remote.origin.pushurl" seems correct.');
        } else {
            // @todo: Provide command to configure git
            $event->getIO()->write('<fg=red;options=bold>✘</> Git "remote.origin.pushurl" not set correctly, please run "composer tdk:set-git-config".');
        }

        // Test commit template
        $processCommitTemplate = new Process(['git', 'config', '--get', 'commit.template'], $this->coreDevFolder);
        $processCommitTemplate->setTty(false);
        $processCommitTemplate->run();

        if (!empty($outputTemplate) && $filesystem->exists(trim($outputTemplate))) {
            $event->getIO()->write('<fg=green;options=bold>✔</> Git "commit.template" is set to ' . trim($outputTemplate) . '.');
        } else {
            // @todo: Provide command to set the commit template
            $event->getIO()->write('<fg=red;options=bold>✘</> Git "commit.template" not set or file does not exist, please run "composer tdk:set-commit-template"');
        }

        // Test vendor folder
        if ($filesystem->exists('vendor')) {
            $event->getIO()->write('<fg=green;options=bold>✔</> Vendor folder exists.');
        } else {
            $event->getIO()->write('<fg=red;options=bold>✘</> Vendor folder is missing, please run "composer install"');
        }
    }
}
