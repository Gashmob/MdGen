<?php

namespace Gashmob\Mdgen\Test\fulls\NestedCondition;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class NestedConditionTest implements Test
{
    /**
     * @inheritDoc
     * @throws ParserStateException|FileNotFoundException
     */
    public function run()
    {
        $engine = new MdGenEngine();
        $html = $engine->render(__DIR__ . '/template.mdt', [
            'foo' => 'bar',
            'hello' => 'world!',
        ]);

        return $html == "<p>Foo is equal to bar</p>\n<p>And hello is not equal to world</p>\n";
    }
}