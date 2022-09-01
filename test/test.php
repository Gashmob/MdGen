#!/usr/bin/php
<?php

require_once '../vendor/autoload.php';

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\MdGenEngine;

$engine = new MdGenEngine();

// Get all dirs in templates dir
$dirs = glob('./templates/*', GLOB_ONLYDIR);

$passed = 0;
foreach ($dirs as $dir) {
    $dirname = basename($dir);
    echo 'Testing ' . $dirname . ' ';
    $file = $dir . '/' . $dirname . '.mdt';

    try {
        $html = $engine->render($file);
    } catch (FileNotFoundException $e) {
        echo "\033[41m FAIL \033[0m " . $e->getMessage() . "\n";
        continue;
    }

    $right_value = file_get_contents($dir . '/' . $dirname . '.html');

    if ($html == $right_value) {
        echo "\033[42m PASS \033[0m\n";
        $passed++;
    } else {
        echo "\033[41m FAIL \033[0m\n";
    }
}

echo "\n";
echo 'Passed: ' . $passed . '/' . count($dirs) . "\n";
if ($passed == count($dirs)) {
    echo "\033[42m ALL TESTS PASSED \033[0m\n";
    exit(0);
} else {
    echo "\033[41m SOME TESTS FAILED \033[0m\n";
    exit(1);
}