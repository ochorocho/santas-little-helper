<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Symfony\Component\Console\Logger\ConsoleLogger;

class BaseService
{
    public const string CORE_REPO_CACHE = 'typo3-core';
    public const string REVIEW_SERVER = 'review.typo3.org:29418/Packages/TYPO3.CMS.git';

    protected string $coreDevFolder = 'typo3-core';
    protected string $reviewServer = 'review.typo3.org:29418/Packages/TYPO3.CMS.git';

    protected ConsoleLogger $logger;

    public function __construct(ConsoleLogger $logger)
    {
        $this->logger = $logger;
    }

    public function infoCommand(string $message): void
    {
        $this->logger->debug('Running command: ' . $message);
    }
}
