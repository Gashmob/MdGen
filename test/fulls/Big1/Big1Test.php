<?php

namespace Gashmob\Mdgen\Test\fulls\Big1;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class Big1Test implements Test
{
    /**
     * @inheritDoc
     * @throws ParserStateException|FileNotFoundException
     */
    public function run()
    {
        $engine = new MdGenEngine();
        $engine->includePath(__DIR__ . '/');
        $engine->basePath(__DIR__ . '/');

        $html = $engine->render(__DIR__ . '/template.mdt', [
            'nbArticles' => 3,
            'articles' => [
                [
                    'title' => 'Article 1',
                    'content' => 'Article 1 content',
                ],
                [
                    'title' => 'Article 2',
                    'content' => 'Article 2 content',
                ],
                [
                    'title' => 'Article 3',
                    'content' => 'Article 3 content',
                ],
            ],
        ]);

        return $html == file_get_contents(__DIR__ . '/result.html');
    }
}