<?php

declare(strict_types=1);

namespace Ochorocho\SantasLittleHelper\Service;

class ValidatorService
{
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
