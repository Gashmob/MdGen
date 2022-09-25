<?php

namespace Gashmob\Mdgen;

use Gashmob\Mdgen\exceptions\FileNotFoundException;
use Gashmob\Mdgen\exceptions\ParserStateException;

class MdGenEngine
{
    /**
     * @var string
     */
    private static $includePath = './';
    /**
     * @var string
     */
    private static $basePath = './';
    /**
     * @var string|false
     */
    private static $cache = false;
    /**
     * @var int Lifespan of cache in seconds
     */
    public static $cacheLifespan = 30 * 24 * 60 * 60; // 1 month

    public function __construct()
    {
    }

    public function includePath($path)
    {
        self::$includePath = $path;
    }

    public static function getIncludePath()
    {
        return self::$includePath;
    }

    public function basePath($path)
    {
        self::$basePath = $path;
    }

    public static function getBasePath()
    {
        return self::$basePath;
    }

    public function cache($cache)
    {
        if ($cache) {
            if (!is_dir($cache)) {
                mkdir($cache, 0777, true);
            }
        }
        self::$cache = $cache;
    }

    /**
     * @param $filename string The path to the template file
     * @return string[] The list of key value pairs at the beginning of the template
     * @throws FileNotFoundException
     */
    public function preRender($filename)
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException($filename);
        }

        $content = file_get_contents($filename);
        $lines = explode("\n", $content);

        $result = [];

        $regex = /** @lang PhpRegExp */
            "/^\[#]: +(.+?) +-> +(.+?) *$/"; // Match on [#]: <key> -> <value>
        foreach ($lines as $line) {
            $matches = [];
            if (preg_match($regex, $line, $matches)) {
                $result[$matches[1]] = $matches[2];
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Render a template
     *
     * @param $filename string The path to the template file
     * @param $values string[] The values to use in the template
     * @return string The html corresponding to the template
     * @throws FileNotFoundException|ParserStateException
     */
    public function render($filename, $values = [])
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException($filename);
        }

        $writer = new IndentWriter('    ');

        $content = file_get_contents($filename);

        if (self::$cache) {
            $cacheFile = self::$cache . '/' . md5($content);
            $limit = time() - self::$cacheLifespan;
            if (file_exists($cacheFile) && filemtime($cacheFile) > $limit) {
                return file_get_contents($cacheFile);
            }
        }

        $lines = explode("\n", $content);

        $parser = new MdParser($lines, $writer);
        $parser->parse($values);

        $result = $writer->getBuffer();

        if (self::$cache) {
            assert(isset($cacheFile));
            file_put_contents($cacheFile, $result);
        }

        return $result;
    }


}