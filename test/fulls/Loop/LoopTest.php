<?php

namespace Gashmob\Mdgen\Test\fulls\Loop;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class LoopTest implements Test
{
    /**
     * @inheritDoc
     * @throws ParserStateException|FileNotFoundException
     */
    public function run()
    {
        $engine = new MdGenEngine();
        $html = $engine->render(__DIR__ . '/template.mdt', [
            'tab' => [1, 2, 3],
        ]);

        return $html == "<ul>\n    <li>\n        1\n    </li>\n    <li>\n        2\n    </li>\n    <li>\n        3\n    </li>\n</ul>\n";
    }
}