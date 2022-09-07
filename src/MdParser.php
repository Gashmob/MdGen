<?php

namespace Gashmob\Mdgen;

use Gashmob\Mdgen\exceptions\ParserStateException;

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
    const OLIST = /** @lang PhpRegExp */
        "/^(( {4})*)(\d+)\. +(.*)$/";
    const ULIST = /** @lang PhpRegExp */
        "/^(( {4})*)- +(.*)$/";
    const HR = /** @lang PhpRegExp */
        "/^---+ *$/";
    const BCODEB = /** @lang PhpRegExp */
        "/^```(.*?) *$/";
    const BCODEE = /** @lang PhpRegExp */
        "/^``` *$/";
    const QUOTE = /** @lang PhpRegExp */
        "/^> +(.*)$/";

    /**
     * @throws ParserStateException
     */
    public function parse()
    {
        $state = new EngineState();

        foreach ($this->lines as $line) {
            $line = rtrim($line);

            $state = $this->parseLine($state, $line);
        }

        if ($state->state != EngineState::INIT) {
            $this->finishBalise($state);
        }
    }

    /**
     * @param EngineState $state
     * @param string $line
     * @throws ParserStateException
     */
    private function parseLine($state, $line)
    {
        switch ($state->state) {
            case EngineState::INIT:
                return $this->parseInit($line);

            case EngineState::OLIST:
                return $this->parseOList($line, $state);

            case EngineState::ULIST:
                return $this->parseUList($line, $state);

            case EngineState::CODE:
                return $this->parseCode($line);

            case EngineState::QUOTE:
                return $this->parseQuote($line, $state);

            default:
                throw new ParserStateException();
        }
    }

    /**
     * @param string $line
     * @return EngineState
     */
    private function parseInit($line)
    {
        $matches = [];

        if (preg_match(self::TITLE, $line, $matches)) {
            $level = min(strlen($matches[1]), 6);
            $this->writer->writeIndent("<h$level>\n");
            $this->writer->indent();
            $this->writer->writeIndent($this->parseInLine($matches[2]));
            $this->writer->unindent();
            $this->writer->writeIndent("</h$level>\n");
            return new EngineState();
        }

        if (preg_match(self::OLIST, $line, $matches)) {
            $this->writer->writeIndent("<ol>\n");
            $this->writer->indent();
            $this->writer->writeIndent("<li>\n");
            $this->writer->indent();
            $this->writer->writeIndent($this->parseInLine($matches[4]));
            return new EngineState(EngineState::OLIST, 0);
        }

        if (preg_match(self::ULIST, $line, $matches)) {
            $this->writer->writeIndent("<ul>\n");
            $this->writer->indent();
            $this->writer->writeIndent("<li>\n");
            $this->writer->indent();
            $this->writer->writeIndent($this->parseInLine($matches[3]));
            return new EngineState(EngineState::ULIST, 0);
        }

        if (preg_match(self::HR, $line, $matches)) {
            $this->writer->writeIndent("<hr/>\n");
            return new EngineState();
        }

        if (preg_match(self::BCODEB, $line, $matches)) {
            $this->writer->writeIndent("<pre><code class=\"language-$matches[1]\">\n");
            return new EngineState(EngineState::CODE);
        }

        if (preg_match(self::QUOTE, $line, $matches)) {
            $this->writer->writeIndent("<blockquote>\n");
            $this->writer->indent();
            $this->writer->writeIndent($this->parseInLine($matches[1]));
            return new EngineState(EngineState::QUOTE);
        }

        // If no match, use parseInLine (if line is not empty)
        if ($line != '') {
            $this->writer->writeIndent("<p>" . $this->parseInLine($line, false) . "</p>\n");
        }

        return new EngineState();
    }

    /**
     * @param string $line
     * @param EngineState $state
     * @return EngineState
     */
    private function parseOList($line, $state)
    {
        if (preg_match(self::OLIST, $line, $matches)) {
            $level = strlen($matches[1]) / 4;
            if ($level > $state->level) {
                $this->writer->writeIndent("<ol>\n");
                $this->writer->indent();
            } else if ($level < $state->level) {
                for ($i = $state->level; $i > $level; $i--) {
                    $this->writer->unindent();
                    $this->writer->writeIndent("</li>\n");
                    $this->writer->unindent();
                    $this->writer->writeIndent("</ol>\n");
                }
                $this->writer->unindent();
                $this->writer->writeIndent("</li>\n");
            } else {
                $this->writer->unindent();
                $this->writer->writeIndent("</li>\n");
            }

            $this->writer->writeIndent("<li>\n");
            $this->writer->indent();
            $this->writer->writeIndent($this->parseInLine($matches[4]));
            return new EngineState(EngineState::OLIST, $level);
        }

        if (preg_match(self::ULIST, $line, $matches)) {
            $level = strlen($matches[1]) / 4;
            if ($level > $state->level) {
                $this->writer->writeIndent("<ul>\n");
                $this->writer->indent();
            } else if ($level < $state->level) {
                for ($i = $state->level; $i > $level; $i--) {
                    $this->writer->unindent();
                    $this->writer->writeIndent("</li>\n");
                    $this->writer->unindent();
                    $this->writer->writeIndent("</ol>\n");
                }
                $this->writer->unindent();
                $this->writer->writeIndent("</li>\n");
            } else {
                $this->writer->unindent();
                $this->writer->writeIndent("</li>\n");
            }

            $this->writer->writeIndent("<li>\n");
            $this->writer->indent();
            $this->writer->writeIndent($this->parseInLine($matches[3]));
            return new EngineState(EngineState::ULIST, $level);
        }

        for ($i = -1; $i < $state->level; $i++) {
            $this->writer->unindent();
            $this->writer->writeIndent("</li>\n");
            $this->writer->unindent();
            $this->writer->writeIndent("</ol>\n");
        }

        return $this->parseInit($line);
    }

    /**
     * @param string $line
     * @param EngineState $state
     * @return EngineState
     */
    private function parseUList($line, $state)
    {
        if (preg_match(self::ULIST, $line, $matches)) {
            $level = strlen($matches[1]) / 4;
            if ($level > $state->level) {
                $this->writer->writeIndent("<ul>\n");
                $this->writer->indent();
            } else if ($level < $state->level) {
                for ($i = $state->level; $i > $level; $i--) {
                    $this->writer->unindent();
                    $this->writer->writeIndent("</li>\n");
                    $this->writer->unindent();
                    $this->writer->writeIndent("</ul>\n");
                }
                $this->writer->unindent();
                $this->writer->writeIndent("</li>\n");
            } else {
                $this->writer->unindent();
                $this->writer->writeIndent("</li>\n");
            }

            $this->writer->writeIndent("<li>\n");
            $this->writer->indent();
            $this->writer->writeIndent($this->parseInLine($matches[3]));
            return new EngineState(EngineState::ULIST, $level);
        }

        if (preg_match(self::OLIST, $line, $matches)) {
            $level = strlen($matches[1]) / 4;
            if ($level > $state->level) {
                $this->writer->writeIndent("<ol>\n");
                $this->writer->indent();
            } else if ($level < $state->level) {
                for ($i = $state->level; $i > $level; $i--) {
                    $this->writer->unindent();
                    $this->writer->writeIndent("</li>\n");
                    $this->writer->unindent();
                    $this->writer->writeIndent("</ul>\n");
                }
                $this->writer->unindent();
                $this->writer->writeIndent("</li>\n");
            } else {
                $this->writer->unindent();
                $this->writer->writeIndent("</li>\n");
            }

            $this->writer->writeIndent("<li>\n");
            $this->writer->indent();
            $this->writer->writeIndent($this->parseInLine($matches[4]));
            return new EngineState(EngineState::OLIST, $level);
        }

        for ($i = -1; $i < $state->level; $i++) {
            $this->writer->unindent();
            $this->writer->writeIndent("</li>\n");
            $this->writer->unindent();
            $this->writer->writeIndent("</ul>\n");
        }

        return $this->parseInit($line);
    }

    /**
     * @param string $line
     * @return EngineState
     */
    private function parseCode($line)
    {
        if (preg_match(self::BCODEE, $line)) {
            $this->writer->writeIndent("</code></pre>\n");
            return new EngineState();
        }

        $this->writer->write($line . "\n");

        return new EngineState(EngineState::CODE);
    }

    /**
     * @param string $line
     * @return EngineState
     */
    private function parseQuote($line)
    {
        $matches = [];
        if (preg_match(self::QUOTE, $line, $matches)) {
            $this->writer->writeIndent($this->parseInLine($matches[1]));
            return new EngineState(EngineState::QUOTE);
        }

        $this->writer->unindent();
        $this->writer->writeIndent("</blockquote>\n");

        return $this->parseInit($line);
    }

    const BOLD = /** @lang PhpRegExp */
        "/\*\*([^ ].*?[^ ])\*\*/";
    const ITALIC = /** @lang PhpRegExp */
        "/\*([^ ].*?[^ ])\*/";
    const CODE = /** @lang PhpRegExp */
        "/`(.*?)`/";
    const IMAGE = /** @lang PhpRegExp */
        "/^!\[(.*?)]\((.*?)\)$/";
    const LINK = /** @lang PhpRegExp */
        "/^\[(.*?)]\((.*?)\)$/";

    /**
     * @param string $line
     * @param bool $addNL
     * @return string
     */
    private function parseInLine($line, $addNL = true)
    {
        if ($line === '') {
            return $line;
        }

        $line = preg_replace(self::BOLD, '<strong>$1</strong>', $line);
        $line = preg_replace(self::ITALIC, '<em>$1</em>', $line);
        $line = preg_replace(self::CODE, '<code>$1</code>', $line);
        $line = preg_replace(self::IMAGE, '<img src="$2" alt="$1"/>', $line);
        $line = preg_replace(self::LINK, '<a href="$2">$1</a>', $line);

        return $line . ($addNL ? "\n" : "");
    }

    /**
     * @param EngineState $state
     * @return void
     */
    private function finishBalise($state)
    {
        switch ($state->state) {
            case EngineState::OLIST:
                for ($i = -1; $i < $state->level; $i++) {
                    $this->writer->unindent();
                    $this->writer->writeIndent("</li>\n");
                    $this->writer->unindent();
                    $this->writer->writeIndent("</ol>\n");
                }
                break;

            case EngineState::ULIST:
                for ($i = -1; $i < $state->level; $i++) {
                    $this->writer->unindent();
                    $this->writer->writeIndent("</li>\n");
                    $this->writer->unindent();
                    $this->writer->writeIndent("</ul>\n");
                }
                break;
        }
    }
}