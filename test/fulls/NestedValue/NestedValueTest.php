<?php

namespace Gashmob\Mdgen\Test\fulls\NestedValue;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class NestedValueTest implements Test
{
    /**
     * @inheritDoc
     * @throws FileNotFoundException|ParserStateException
     */
    public
    function run()
    {
        $engine = new MdGenEngine();
        $html = $engine->render(__DIR__ . '/template.mdt', [
            "foo" => [
                "bar" => 42
            ],
            "A" => new A("Hello World!")
        ]);

        return $html == "<p>This is the value of foo.bar : 42</p>\n<p>And I want to say Hello World!</p>\n";
    }
}