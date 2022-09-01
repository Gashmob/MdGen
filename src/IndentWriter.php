<?php

namespace Gashmob\Mdgen;

/**
 * Class IndentWriter
 *
 * Allow to write indented text easier.
 */
class IndentWriter
{
    /**
     * @var string
     */
    private $buffer;

    /**
     * @var int
     */
    private $indent;

    /**
     * @var string The string used to indent.
     */
    public $indentChar;

    /**
     * @param string $indentChar
     */
    public function __construct($indentChar = "\t")
    {
        $this->indentChar = $indentChar;
    }

    public function indent()
    {
        $this->indent++;
    }

    public function unindent()
    {
        $this->indent--;
    }

    public function write($text)
    {
        $this->buffer .= $text;
    }

    public function writeIndent($text)
    {
        $this->write(str_repeat($this->indentChar, $this->indent) . $text);
    }

    public function getBuffer()
    {
        return $this->buffer;
    }
}