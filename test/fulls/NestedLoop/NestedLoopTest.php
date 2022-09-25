<?php

namespace Gashmob\Mdgen\Test\fulls\NestedLoop;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class NestedLoopTest implements Test
{

    /**
     * @inheritDoc
     * @throws FileNotFoundException|ParserStateException
     */
    public function run()
    {
        $engine = new MdGenEngine();
        $html = $engine->render(__DIR__ . '/template.mdt', [
            'tab' => [1, 2, 3],
            'tab2' => [1, 2],
        ]);

        return $html == file_get_contents(__DIR__ . '/result.html');
    }
}