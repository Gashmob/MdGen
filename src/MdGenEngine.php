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

        $regex = /** @lang PhpRegExp */
            "/^\[#]: *(.*?) *-> *(.*?)$/"; // Match on [#]: <key> -> <value>
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
     * @throws FileNotFoundException
     */
    public function render($filename)
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException($filename);
        }

        $writer = new IndentWriter();

        $content = file_get_contents($filename);
        $lines = explode("\n", $content);

        // Regex
        $title = /** @lang PhpRegExp */
            "/^(#+) *(.*)$/";
        $image = /** @lang PhpRegExp */
            "/^!\[(.*?)]\((.*?)\)$/";
        $link = /** @lang PhpRegExp */
            "/^\[(.*?)]\((.*?)\)$/";

        $state = EngineState::$STATE_INIT;
        foreach ($lines as $line) {
            $matches = [];

            // Titles
            if (preg_match($title, $line, $matches)) {
                $level = min(strlen($matches[1]), 6);
                $writer->writeIndent("<h$level>$matches[2]</h$level>\n");
            } // Images
            else if (preg_match($image, $line, $matches)) {
                $writer->writeIndent("<img src=\"$matches[2]\" alt=\"$matches[1]\"/>\n");
            } // Link
            else if (preg_match($link, $line, $matches)) {
                $writer->writeIndent("<a href=\"$matches[2]\">$matches[1]</a>\n");
            }
        }

        return $writer->getBuffer();
    }
}