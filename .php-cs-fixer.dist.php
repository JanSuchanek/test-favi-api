<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        // ensure spaces around string concatenation to match PSR-12 / phpcs expectations
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setFinder($finder)
;
