#!/usr/bin/php
<?php

require_once '../vendor/autoload.php';

use Gashmob\Mdgen\MdGenEngine;

function message($msg)
{
    echo "\033[44m $msg \033[0m\n";
}

function pass()
{
    echo "\033[42m PASS \033[0m\n";
}

function fail($message = "")
{
    echo "\033[41m FAIL \033[0m" . ($message != "" ? " $message\n" : "\n");
}

$passed = 0;
$total = 0;

// _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.
// Tests templates

message("Testing templates");

// Get all dirs in templates dir
$dirs = glob('./templates/*', GLOB_ONLYDIR);
$total += count($dirs);

$engine = new MdGenEngine();
foreach ($dirs as $dir) {
    $dirname = basename($dir);
    echo 'Testing ' . $dirname . ' ';
    $file = $dir . '/' . $dirname . '.mdt';

    try {
        $html = $engine->render($file);
    } catch (Exception $e) {
        fail($e->getMessage());
        continue;
    }

    $right_value = file_get_contents($dir . '/' . $dirname . '.html');

    if ($html == $right_value) {
        pass();
        $passed++;
    } else {
        fail("Expected:\n|$right_value|Got:\n|$html|");
    }
}

// _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.
// Tests fulls

message("Testing fulls");

// Get all dirs in templates dir
$dirs = glob('./fulls/*', GLOB_ONLYDIR);
$total += count($dirs);

foreach ($dirs as $dir) {
    $dirname = basename($dir);
    echo 'Testing ' . $dirname . ' ';

    require_once $dir . '/' . $dirname . 'Test.php';

    try {
        $class = 'Gashmob\\Mdgen\\Test\\fulls\\' . $dirname . '\\' . $dirname . 'Test';
        $test = new $class();
        $result = $test->run();
    } catch (Exception $e) {
        fail($e->getMessage());
        continue;
    }

    if ($result) {
        pass();
        $passed++;
    } else {
        fail();
    }
}

// _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.

echo "\n";
echo 'Passed: ' . $passed . '/' . $total . "\n";
if ($passed == $total) {
    echo "\033[42m ALL TESTS PASSED \033[0m\n";
    exit(0);
} else {
    echo "\033[41m SOME TESTS FAILED \033[0m\n";
    exit(1);
}