<?php

namespace Gashmob\Mdgen\Test\fulls\Include1;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class Include1Test implements Test
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

        return $html == "Just after, we will have a lorem ipsum\nLorem ipsum dolor sit amet...\nJust before, we have a lorem ipsum\n";
    }
}