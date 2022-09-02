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
    const ORDER_LIST = /** @lang PhpRegExp */
        "/^(\d+)\. *(.*)$/";
    const UNORDER_LIST = /** @lang PhpRegExp */
        "/^- *(.*)$/";
    const HR = /** @lang PhpRegExp */
        "/^---+ *$/";

    public function parse()
    {
        foreach ($this->lines as $line) {
            $this->parseLine($line);
        }

        $this->finishBalise();
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
            $this->state = EngineState::TITLE;
            $this->writer->unindent();
            $this->writer->writeIndent("</h$level>\n");
        } // Images
        else if (preg_match(self::IMAGE, $line, $matches)) {
            $this->writer->writeIndent("<img src=\"$matches[2]\" alt=\"$matches[1]\"/>\n");
        } // Link
        else if (preg_match(self::LINK, $line, $matches)) {
            $this->writer->writeIndent("<a href=\"$matches[2]\">\n");
            $this->writer->indent();
            $this->state = EngineState::LINK;
            $this->parseLine($matches[1]);
            $this->state = EngineState::LINK;
            $this->writer->unindent();
            $this->writer->writeIndent("</a>\n");
        } // Bold
        else if (preg_match(self::BOLD, $line, $matches)) {
            $this->writer->writeIndent("<strong>\n");
            $this->writer->indent();
            $this->state = EngineState::BOLD;
            $this->parseLine($matches[1]);
            $this->state = EngineState::BOLD;
            $this->writer->unindent();
            $this->writer->writeIndent("</strong>\n");
        } // Ordered list
        else if (preg_match(self::ORDER_LIST, $line, $matches)) {
            if ($this->state !== EngineState::ORD_LIST) {
                $this->writer->writeIndent("<ol>\n");
                $this->writer->indent();
            }
            $this->writer->writeIndent("<li>\n");
            $this->writer->indent();
            $this->state = EngineState::LIST_ITEM;
            $this->parseLine($matches[2]);
            $this->state = EngineState::ORD_LIST;
            $this->writer->unindent();
            $this->writer->writeIndent("</li>\n");
        } // Unordered list
        else if (preg_match(self::UNORDER_LIST, $line, $matches)) {
            if ($this->state !== EngineState::UNORD_LIST) {
                $this->writer->writeIndent("<ul>\n");
                $this->writer->indent();
            }
            $this->writer->writeIndent("<li>\n");
            $this->writer->indent();
            $this->state = EngineState::LIST_ITEM;
            $this->parseLine($matches[1]);
            $this->state = EngineState::UNORD_LIST;
            $this->writer->unindent();
            $this->writer->writeIndent("</li>\n");
        } // hr
        else if (preg_match(self::HR, $line, $matches) && $this->state === EngineState::STATE_INIT) {
            $this->writer->writeIndent("<hr/>\n");
        } // Text
        else {
            $this->finishBalise();

            if ($line !== '') {
                if ($this->state == EngineState::STATE_INIT) {
                    $this->writer->writeIndent("<p>$line</p>\n");
                } else {
                    $this->writer->writeIndent($line . "\n");
                }
            }
        }
    }

    private function finishBalise()
    {
        if ($this->state === EngineState::ORD_LIST) {
            $this->writer->unindent();
            $this->writer->writeIndent("</ol>\n");
            $this->state = EngineState::STATE_INIT;
        } else if ($this->state === EngineState::UNORD_LIST) {
            $this->writer->unindent();
            $this->writer->writeIndent("</ul>\n");
            $this->state = EngineState::STATE_INIT;
        }
    }
}