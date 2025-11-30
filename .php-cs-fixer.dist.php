<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->setUnsupportedPhpVersionAllowed(true);

$config->getFinder()->in(__DIR__);
$config->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());
$config->addRules([
    'function_declaration' => [
        'closure_function_spacing' => 'one',
    ],
]);
return $config;
