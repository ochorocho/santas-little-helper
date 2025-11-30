<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Validator;

use Ochorocho\SantasLittleHelper\Service\GerritService;

readonly class SetupValidator
{
    public function username(): \Closure
    {
        return function ($value): array {
            $username = $value ?? '';
            $userData = (new GerritService())->getGerritUserData($username);

            if (!is_string($userData['username'] ?? false)) {
                throw new \RuntimeException('The given username "' . $username . '" was not found on https://review.typo3.org/');
            }

            return $userData;
        };
    }

    public function commitTemplate(): \Closure
    {
        return function ($value): string {
            $templatePath = $value ?? '';

            if (!is_file($templatePath)) {
                throw new \RuntimeException('The given template "' . $templatePath . '" file was not found.');
            }

            return $templatePath;
        };
    }

    public function projectName(): \Closure
    {
        return function ($value) {
            if (!preg_match('/^[a-zA-Z0-9_-]*$/', trim($value))) {
                throw new \UnexpectedValueException('Invalid ddev project name "' . $value . '"');
            }

            return trim($value);
        };
    }

    public function filePath(): \Closure
    {
        return function ($value) {
            if (!is_file($value)) {
                throw new \UnexpectedValueException('Invalid file path "' . $value . '"');
            }

            return $value;
        };
    }
}
