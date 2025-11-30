<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Factory;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleLoggerFactory
{
    public static function create(OutputInterface $output): ConsoleLogger
    {
        $verbosityLevelMap = [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO   => OutputInterface::VERBOSITY_VERBOSE,
            LogLevel::DEBUG  => OutputInterface::VERBOSITY_VERY_VERBOSE,
        ];

        return new ConsoleLogger($output, $verbosityLevelMap);
    }
}
