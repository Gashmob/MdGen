<?php

namespace Gashmob\Mdgen\Test\fulls\Base1;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class Base1Test implements Test
{
    /**
     * @inheritDoc
     * @throws FileNotFoundException|ParserStateException
     */
    public function run()
    {
        $engine = new MdGenEngine();
        $engine->basePath(__DIR__ . '/');
        $template = __DIR__ . '/template.mdt';
        $html = $engine->render($template);

        return $html == "<ul>\n    <li>\n        Here is a link :\n    </li>\n</ul>\n<a href=\"https://www.google.com\">Google</a>\n<ul>\n    <li>\n        And another link :\n    </li>\n</ul>\n<a href=\"https://github.com\">Github</a>\n";
    }
}