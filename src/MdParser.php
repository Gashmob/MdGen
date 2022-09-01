<?php

namespace Gashmob\Mdgen;

class MdParser
{
    /**
     * @var string[]
     */
    private $lines;

    /**
     * @var IndentWriter
     */
    private $writer;

    /**
     * @var int
     */
    private $state = EngineState::STATE_INIT;

    /**
     * @param array $lines
     * @param IndentWriter $writer
     */
    public function __construct(array $lines, IndentWriter $writer)
    {
        $this->lines = $lines;
        $this->writer = $writer;
    }

    // Regex
    const TITLE = /** @lang PhpRegExp */
        "/^(#+) *(.*)$/";
    const IMAGE = /** @lang PhpRegExp */
        "/^!\[(.*?)]\((.*?)\)$/";
    const LINK = /** @lang PhpRegExp */
        "/^\[(.*?)]\((.*?)\)$/";
    const BOLD = /** @lang PhpRegExp */
        "/^\*\*(.*?)\*\*$/";

    public function parse()
    {
        foreach ($this->lines as $line) {
            $this->parseLine($line);
        }
    }

    /**
     * @param string $line
     */
    private function parseLine($line)
    {
        $matches = [];

        // Titles
        if (preg_match(self::TITLE, $line, $matches)) {
            $level = min(strlen($matches[1]), 6);
            $this->writer->writeIndent("<h$level>\n");
            $this->writer->indent();
            $this->state = EngineState::TITLE;
            $this->parseLine($matches[2]);
            $this->writer->unindent();
            $this->writer->writeIndent("</h$level>\n");
        } // Images
        else if (preg_match(self::IMAGE, $line, $matches)) {
            $this->writer->writeIndent("<img src=\"$matches[2]\" alt=\"$matches[1]\"/>\n");
        } // Link
        else if (preg_match(self::LINK, $line, $matches)) {
            $this->writer->writeIndent("<a href=\"$matches[2]\">$matches[1]</a>\n");
        } // Bold
        else if (preg_match(self::BOLD, $line, $matches)) {
            $this->writer->writeIndent("<strong>\n");
            $this->writer->indent();
            $this->state = EngineState::BOLD;
            $this->parseLine($matches[1]);
            $this->writer->unindent();
            $this->writer->writeIndent("</strong>\n");
        } // Text
        else if ($line !== '') {
            if ($this->state == EngineState::STATE_INIT) {
                $this->writer->writeIndent("<p>$line</p>\n");
            } else {
                $this->writer->writeIndent($line . "\n");
            }
        }
    }
}