<?php

require_once './vendor/autoload.php';

$finder = \Symfony\CS\Finder\DefaultFinder::create()
    ->in('src/')
    ->in('tests/');

return \Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->fixers([
        '-psr0', // Because it causes such grief with the Tests namespace in the tests folder
        '-single_blank_line_before_namespace',
        '-pre_increment',
        '-concat_without_spaces',
        '-phpdoc_inline_tag',
        'concat_with_spaces',
        'ordered_use',
    ])
    ->finder($finder);
