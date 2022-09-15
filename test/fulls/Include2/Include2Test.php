<?php

namespace Gashmob\Mdgen\Test\fulls\Include2;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class Include2Test implements Test
{
    /**
     * @inheritDoc
     * @throws FileNotFoundException|ParserStateException
     */
    public function run()
    {
        $engine = new MdGenEngine();
        $engine->includePath(__DIR__ . '/');
        $template = __DIR__ . '/template.mdt';
        $html = $engine->render($template);

        return $html == "<p>Just after, we will have some values</p>\n<p>The 2 values are bar and 42.</p>\n<p>Just before, we have some values</p>\n";
    }
}