<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Validator;

use Ochorocho\SantasLittleHelper\Service\GerritService;

readonly class UserValidator
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
}
