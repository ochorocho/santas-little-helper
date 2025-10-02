<?php

namespace Ochorocho\SantasLittleHelper\Logger;

use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger extends \Symfony\Component\Console\Logger\ConsoleLogger {
    private OutputInterface $output;

    public function __construct(OutputInterface $output, array $verbosityLevelMap = [], array $formatLevelMap = [])
    {
        $this->output = $output;
        parent::__construct($output, $verbosityLevelMap, $formatLevelMap);
    }

    public function out(array|string $message): void {
        $this->output->writeln($message);
    }

    public function command(array|string $message): void
    {
        $this->output->writeln(['<comment>Running command:</comment>', ...$message]);
    }
}