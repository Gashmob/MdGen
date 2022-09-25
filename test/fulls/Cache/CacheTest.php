<?php

namespace Gashmob\Mdgen\Test\fulls\Cache;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;
use Gashmob\Mdgen\MdGenEngine;
use Gashmob\Mdgen\Test\fulls\Test;

class CacheTest implements Test
{
    /**
     * @inheritDoc
     * @throws FileNotFoundException|ParserStateException
     */
    public function run()
    {
        // Before
        $this->delTree(__DIR__ . '/cache');

        // =====

        $engine = new MdGenEngine();
        $engine->cache(__DIR__ . '/cache');
        $engine->render(__DIR__ . '/template.mdt');

        $dir = scandir(__DIR__ . '/cache');
        $result = is_dir(__DIR__ . '/cache') && is_array($dir) && count($dir) == 3;

        // =====

        // After
        $engine->cache(false);
        $this->delTree(__DIR__ . '/cache');

        return $result;
    }

    private function delTree($dir)
    {
        if (file_exists($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
            }
            rmdir($dir);
        }
    }
}