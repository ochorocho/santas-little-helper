<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Application;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class HelperApplication extends Application
{
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        // @todo: I don't like this. Use getDefinition or something?!
        $rawArgs = $_SERVER['argv'] ?? [];

        // Special treatment for "composer --help" to get the actual output
        $isComposerHelp = count($rawArgs) >= 3 && $rawArgs[1] === 'composer' && (in_array('--help', $rawArgs) || in_array('-h', $rawArgs));
        if ($isComposerHelp) {
            $composerApp = new \Composer\Console\Application();
            $composerApp->setAutoExit(false);
            $composerApp->setCatchExceptions(false);

            $composerInput = new StringInput('--help');
            return $composerApp->run($composerInput, $output);
        }

        return parent::run($input, $output);
    }
}