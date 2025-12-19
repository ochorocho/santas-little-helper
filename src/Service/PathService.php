<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

use Symfony\Component\Filesystem\Exception\RuntimeException;

class PathService
{
    protected string $configFolder = '.santas-little-helper';

    public function getConfigFolder(): string
    {
        try {
            $configFolder = $this->getConfigPath();
        } catch (RuntimeException) {
            $configFolder = $this->createConfigFolder();
        }

        return $configFolder;
    }

    public function getConfigPath(): string
    {
        $absolutePath = $this->getHomeDirectory() . '/' . $this->configFolder;
        if (!is_dir($absolutePath)) {
            throw new RuntimeException("Config folder '{$absolutePath}' does not exist");
        }

        return $this->getHomeDirectory() . '/' . $this->configFolder;
    }

    public function pharExtractFileToConfigBin(string $path): void
    {
        $configFolder = $this->getConfigFolder();
        $basePath = str_replace('/' . basename($path), '', $path);
        $binaryPath = $configFolder . '/' . $basePath;

        if (!is_dir($binaryPath)) {
            mkdir($binaryPath);
        }

        $filePath = $configFolder . '/' . $path;
        //        if(!file_exists($filePath)) {
        $phar = new \Phar('phar://' . $this->getPharPath());
        $phar->extractTo($configFolder, $path, true);
        chmod($filePath, 0755);
        //        }
    }

    private function createConfigFolder(): string
    {
        $configFolder = $this->getHomeDirectory() . '/' . $this->configFolder;
        if (mkdir($configFolder, 0777, true)) {
            return $configFolder;
        }

        throw new RuntimeException('Could not create folder ' . $this->getHomeDirectory() . '/' . $this->configFolder);
    }

    private function getHomeDirectory(): string
    {
        // Windows
        if (PHP_OS_FAMILY === 'Windows') {
            return realpath($_SERVER['USERPROFILE'] ?? $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH']);
        }

        // Linux, macOS
        if (!empty($_SERVER['HOME'])) {
            return realpath($_SERVER['HOME']);
        }

        // Fallback to posix if HOME is not set
        if (function_exists('posix_getpwuid')) {
            $homeDir = posix_getpwuid(getmyuid())['dir'] ?? null;
            if ($homeDir !== null) {
                return realpath($homeDir);
            }
        }

        throw new RuntimeException('Home directory not found.');
    }

    private function getPharPath(): string
    {
        return $_SERVER['SCRIPT_NAME'];
    }
}
