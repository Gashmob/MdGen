<?php

namespace Gashmob\Mdgen\Test\fulls\Condition;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class ConditionTest implements Test
{
    /**
     * @inheritDoc
     * @throws FileNotFoundException|ParserStateException
     */
    public function run()
    {
        $engine = new MdGenEngine();
        $html1 = $engine->render(__DIR__ . '/template.mdt', [
            'foo' => 'bar',
        ]);
        $html2 = $engine->render(__DIR__ . '/template.mdt', [
            'foo' => 'baz',
        ]);

        return $html1 == "<p>a</p>\n" && $html2 == "<p>b</p>\n";
    }
}