<?php

namespace Gashmob\Mdgen\Test\fulls\KeyValue2;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class KeyValue2Test implements Test
{
    /**
     * @inheritDoc
     * @throws FileNotFoundException|ParserStateException
     */
    public function run()
    {
        $engine = new MdGenEngine();
        $template = __DIR__ . '/template.mdt';
        $values = $engine->preRender($template);
        $html = $engine->render($template, $values);

        return $html == "<p>Hi world! The best number is 42 but i prefer 3.14, no you ? ...</p>\n";
    }
}