#!/usr/bin/php
<?php

require_once '../src/MdGenEngine.php';

$engine = new Gashmob\MdGen\MdGenEngine();

// Get all dirs in templates dir
$dirs = glob('./templates/*', GLOB_ONLYDIR);

$passed = 0;
foreach ($dirs as $dir) {
    $nb = $dir[strlen($dir) - 1];
    echo 'Testing ' . $nb . ' ';
    $file = $dir . '/' . $nb . '.md';
    $html = $engine->render($file);

    $right_value = file_get_contents($dir . '/' . $dir[strlen($dir) - 1] . '.html');

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
} else {
    echo "\033[41m SOME TESTS FAILED \033[0m\n";
}