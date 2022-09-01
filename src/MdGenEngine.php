<?php

namespace Gashmob\Mdgen;

use Gashmob\Mdgen\exceptions\FileNotFoundException;

class MdGenEngine
{
    public function __construct()
    {
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

        /** @lang PhpRegExp */
        $regex = "/^\[#]: *(.*?) *-> *(.*?)$/"; // Match on [#]: <key> -> <value>
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
     * @return string The html corresponding to the template
     */
    public function render($filename)
    {
        return "";
    }
}