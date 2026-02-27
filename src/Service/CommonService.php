<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class CommonService extends BaseService
{
    public function doctor(Event $event): void
    {
        $filesystem = new Filesystem();

        // Test for existing repository
        if ($filesystem->exists(self::CORE_REPO_CACHE . '/.git')) {
            $event->getIO()->write('<fg=green;options=bold>✔</> Repository exists.');
        } else {
            $event->getIO()->write('<fg=red;options=bold>✘</> TYPO3 Repository not in place, please run "composer tdk:clone"');
        }

        // Test if hooks are set up
        if ($filesystem->exists([
            self::CORE_REPO_CACHE . '/.git/hooks/pre-commit',
            self::CORE_REPO_CACHE . '/.git/hooks/commit-msg',
        ])) {
            $event->getIO()->write('<fg=green;options=bold>✔</> All hooks are in place.');
        } else {
            $event->getIO()->write('<fg=red;options=bold>✘</> Hooks are missing please run "composer tdk:enable-hooks".');
        }

        // Test git push url
        $process = new Process(['git', 'config', '--get', 'remote.origin.pushurl'], self::CORE_REPO_CACHE);
        $process->setTty(Process::isTtySupported());
        $process->run();

        preg_match('/^ssh:\/\/(.*)@review\.typo3\.org/', $process->getOutput(), $matches);
        if (!empty($matches)) {
            $event->getIO()->write('<fg=green;options=bold>✔</> Git "remote.origin.pushurl" seems correct.');
        } else {
            // @todo: Provide command to configure git
            $event->getIO()->write('<fg=red;options=bold>✘</> Git "remote.origin.pushurl" not set correctly, please run "composer tdk:set-git-config".');
        }

        // Test commit template
        $processCommitTemplate = new Process(['git', 'config', '--get', 'commit.template'], self::CORE_REPO_CACHE);
        $processCommitTemplate->setTty(Process::isTtySupported());
        $processCommitTemplate->run();
        $outputTemplate = trim($processCommitTemplate->getOutput());

        if ($outputTemplate !== '' && $filesystem->exists($outputTemplate)) {
            $event->getIO()->write('<fg=green;options=bold>✔</> Git "commit.template" is set to ' . $outputTemplate . '.');
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
