<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Application;

use Symfony\Component\Console\Application;
use Composer\Console\Application as ComposerApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class HelperApplication extends Application
{
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {

        // Special treatment for "composer --help" to get the actual output
        $rawArgs = (new ArgvInput($_SERVER['argv']))->getRawTokens();
        $isComposerHelp = count($rawArgs) >= 3 && $rawArgs[1] === 'composer' && (in_array('--help', $rawArgs) || in_array('-h', $rawArgs));
        if ($isComposerHelp) {
            $composerApp = new ComposerApplication();
            $composerApp->setAutoExit(false);
            $composerApp->setCatchExceptions(false);

            $composerInput = new StringInput('--help');
            return $composerApp->run($composerInput, $output);
        }

        return parent::run($input, $output);
    }
}