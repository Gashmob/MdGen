<?php

namespace Gashmob\Mdgen;

use Exception;
use Gashmob\Mdgen\exceptions\FileNotFoundException;
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
    const TABLEL = /** @lang PhpRegExp */
        "/^\|(.*?\|)+ *$/";
    const TABLEH = /** @lang PhpRegExp */
        "/^\|( *:?-+:? *\|)+ *$/";
    const COMMENT = /** @lang PhpRegExp */
        "/^ *\[#]:.*$/";

    const USELESS_LINE = /** @lang PhpRegExp */
        "/^\[#]: +(.+?) +-> +(.+?) *$/"; // Match on [#]: <key> -> <value>
    const BASE = /** @lang PhpRegExp */
        "/^\[#]: *base +(.+?) *$/"; // Match on [#]: base <template>
    const BASE_INCLUDE = /** @lang PhpRegExp */
        "/^\[#]: *baseInclude *$/"; // Match on [#]: baseInclude
    const INCLUDE_ = /** @lang PhpRegExp */
        '/^\[#]: *include *(.+?) *(\{.*})?$/'; // Match on [#]: include <template> { "<key>":<value>, "<key>":<value> }
    const VAR_ = /** @lang PhpRegExp */
        "/\{(.+?)}/"; // Match on {<var>}
    const FOR_ = /** @lang PhpRegExp */
        "/^ *\{% for (.+) in (.+) %} *$/"; // Match on {% for <var> in <vars> %}
    const END_FOR = /** @lang PhpRegExp */
        "/^ *\{% endfor %} *$/"; // Match on {% endfor %}
    const IF_ = /** @lang PhpRegExp */
        "/^ *\{% if (.+) %} *$/"; // Match on {% if <condition> %}
    const ELSE_ = /** @lang PhpRegExp */
        "/^ *\{% else %} *$/"; // Match on {% else %}
    const END_IF = /** @lang PhpRegExp */
        "/^ *\{% endif %} *$/"; // Match on {% endif %}

    /**
     * @var array
     */
    private $values = [];

    /**
     * @param array $values
     * @throws ParserStateException|FileNotFoundException
     */
    public function parse($values = [])
    {
        $this->values = $values;

        $this->lines = $this->parseScript();

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
     * @param array $lines
     * @return array
     * @throws FileNotFoundException|ParserStateException
     */
    private function parseScript($lines = null)
    {
        if ($lines == null) {
            $lines = $this->lines;
        }

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            $matches = [];

            if (preg_match(self::USELESS_LINE, $line)) {
                $lines[$i] = '';
                continue;
            }
            if (preg_match(self::FOR_, $line, $matches)) {
                // Check if var is in values
                $key = $matches[2];
                $value = $this->getValue($this->values, $key);
                if ($value == $key || !is_array($value)) {
                    throw new ParserStateException("For loop: $key is not an array");
                }
                $lines = $this->parseLoop($lines, $i, $matches);
                $i--;
                continue;
            }
            if (preg_match(self::IF_, $line, $matches)) {
                $lines = $this->parseIf($lines, $i, $matches);
                $i--;
                continue;
            }
            if (preg_match(self::BASE, $line, $matches)) {
                $lines[$i] = '';
                if (!file_exists(MdGenEngine::getBasePath() . $matches[1] . '.mdt')) {
                    throw new FileNotFoundException(MdGenEngine::getBasePath() . $matches[1] . '.mdt');
                }
                $this->parseBase($matches[1] . '.mdt', $lines);
                return [];
            }

            $lines[$i] = preg_replace_callback(self::VAR_, function ($matches) use ($line) {
                if (preg_match(self::INCLUDE_, $line)) {
                    return '{' . $matches[1] . '}';
                }
                return $this->parseValue($this->getValue($this->values, $matches[1]));
            }, $line);
        }

        return $lines;
    }

    /**
     * @param string $template
     * @param string[] $includeLines
     * @return void
     * @throws FileNotFoundException|ParserStateException
     */
    private function parseBase($template, $includeLines)
    {
        $content = file_get_contents(MdGenEngine::getBasePath() . $template);
        $lines = explode("\n", $content);
        $lines = $this->parseScript($lines);

        $state = new EngineState();

        foreach ($lines as $line) {
            $line = rtrim($line);

            if (preg_match(self::BASE_INCLUDE, $line)) {
                $includeLines = $this->parseScript($includeLines);
                foreach ($includeLines as $includeLine) {
                    $state = $this->parseLine($state, $includeLine);
                }
                continue;
            }

            $state = $this->parseLine($state, $line);
        }
    }

    /**
     * @param EngineState $state
     * @param string $line
     * @throws ParserStateException|FileNotFoundException
     */
    private function parseLine($state, $line)
    {
        if (preg_match(self::COMMENT, $line) && !preg_match(self::INCLUDE_, $line)) { // Ignore comments
            return $state;
        }

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
                return $this->parseQuote($line);

            case EngineState::TABLE:
            case EngineState::TABLEH:
            case EngineState::TABLEB:
                return $this->parseTable($line, $state);

            default:
                throw new ParserStateException();
        }
    }

    /**
     * @param string $line
     * @return EngineState
     * @throws FileNotFoundException
     * @throws ParserStateException
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

        if (preg_match(self::HR, $line)) {
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

        if (preg_match(self::TABLEL, $line)) {
            $this->writer->writeIndent("<table>\n");
            $this->writer->indent();
            return new EngineState(EngineState::TABLE, -1, $line);
        }

        if (preg_match(self::INCLUDE_, $line, $matches)) {
            if (!file_exists(MdGenEngine::getIncludePath() . $matches[1] . '.mdt')) {
                throw new FileNotFoundException(MdGenEngine::getIncludePath() . $matches[1] . '.mdt');
            }
            $content = file_get_contents(MdGenEngine::getIncludePath() . $matches[1] . '.mdt');
            $lines = explode("\n", $content);
            $parser = new MdParser($lines, $this->writer);
            $includeValues = count($matches) > 2 ? $this->getIncludeValues(trim(substr($matches[2], 1, -1)), $this->values) : [];
            $parser->parse(array_merge($this->values, $includeValues));

            return new EngineState();
        }

        // If no match, use parseInLine (if line is not empty)
        if ($line != '') {
            $this->writer->writeIndent($this->parseInLine($line));
        }

        return new EngineState();
    }

    /**
     * @param string $line
     * @param EngineState $state
     * @return EngineState
     * @throws FileNotFoundException|ParserStateException
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
     * @throws FileNotFoundException|ParserStateException
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
     * @throws FileNotFoundException|ParserStateException
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

    /**
     * @param string[] $lines
     * @param int $idx
     * @param string[] $matches
     * @return string[]
     */
    private function parseLoop($lines, $idx, $matches)
    {
        $level = 0;

        // Add lines before for
        $result = [];
        $i = 0;
        for (; $i < $idx; $i++) {
            $result[] = $lines[$i];
        }

        // Get lines into for
        $to_add = [];
        for (; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (preg_match(self::FOR_, $line)) {
                if (++$level == 1) {
                    continue;
                }
            }
            if (preg_match(self::END_FOR, $line)) {
                if (--$level == 0) {
                    break;
                }
            }
            $to_add[] = $line;
        }

        // Add lines into for
        $key = $matches[1];
        $values = $this->getValue($this->values, $matches[2]);
        for ($j = 0; $j < count($values); $j++) {
            $this->values[$key . $j] = $values[$j];
            for ($k = 0; $k < count($to_add); $k++) {
                $result[] = preg_replace_callback("/\{($key)(\..*)?}/", function ($matches) use ($key, $j) { // FIXME: [#] include { "...":... }
                    if (isset($matches[2])) {
                        return '{' . $key . $j . '.' . $matches[2] . '}';
                    } else {
                        return '{' . $key . $j . '}';
                    }
                }, $to_add[$k]);
            }
        }

        // Add lines after for
        $i++;
        for (; $i < count($lines); $i++) {
            $result[] = $lines[$i];
        }

        return $result;
    }

    /**
     * @param string[] $lines
     * @param int $idx
     * @param string[] $matches
     * @return string[]
     */
    private function parseIf($lines, $idx, $matches)
    {
        $result = [];

        // Add lines before if
        $i = 0;
        for (; $i < $idx; $i++) {
            $result[] = $lines[$i];
        }

        // Get lines into if and else
        $if_ = [];
        $else_ = [];
        $level = 0;
        $else = false;
        for (; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (preg_match(self::IF_, $line)) {
                if (++$level == 1) {
                    continue;
                }
            }
            if (preg_match(self::ELSE_, $line)) {
                if ($level == 1) {
                    $else = true;
                    continue;
                }
            }
            if (preg_match(self::END_IF, $line)) {
                if (--$level == 0) {
                    break;
                }
            }

            if ($else) {
                $else_[] = $line;
            } else {
                $if_[] = $line;
            }
        }

        // Evaluate condition
        if ($this->evaluateCondition($matches[1])) {
            $result = array_merge($result, $if_);
        } else {
            $result = array_merge($result, $else_);
        }

        // Add lines after if
        $i++;
        for (; $i < count($lines); $i++) {
            $result[] = $lines[$i];
        }

        return $result;
    }

    /**
     * @param string $condition
     * @return bool
     */
    private function evaluateCondition($condition)
    {
        $cond = preg_replace_callback("/((['\"]?)[a-zA-Z][a-zA-Z0-9_\-]*(\.[^ ]*)*(['\"]?))/", function ($matches) {
            $len = strlen($matches[2]);
            if ($len == 0) {
                $var_ = $matches[1];
            } else {
                $var_ = substr($matches[1], $len, -$len);
            }
            $value = $this->parseValue($this->getValue($this->values, $var_));
            if (is_string($value)) {
                return "'" . $value . "'";
            } else {
                return $value;
            }
        }, $condition);

        eval('$cond = ' . $cond . ';');
        return $cond;
    }

    const ALIGNL = /** @lang PhpRegExp */
        "/^:-+$/";
    const ALIGNR = /** @lang PhpRegExp */
        "/^-+:$/";
    const ALIGNC = /** @lang PhpRegExp */
        "/^:-+:$/";

    /**
     * @param string $line
     * @param EngineState $state
     * @return EngineState
     * @throws FileNotFoundException|ParserStateException
     */
    private function parseTable($line, $state)
    {
        if (preg_match(self::TABLEH, $line) && $state->table != '') {
            if ($state->state == EngineState::TABLEB) {
                $this->writer->unindent();
                $this->writer->writeIndent("</tbody>\n");
            }

            $this->writer->writeIndent("<thead>\n");
            $this->writer->indent();
            $this->writer->writeIndent("<tr>\n");
            $this->writer->indent();
            $lastline = $state->table;
            $cols = array_values(array_filter(explode('|', $lastline), 'Gashmob\Mdgen\filter_array_empty'));
            $aligns = array_values(array_filter(explode('|', $line), 'Gashmob\Mdgen\filter_array_empty'));
            for ($i = 0; $i < count($aligns); $i++) {
                if (preg_match(self::ALIGNL, $aligns[$i])) {
                    $aligns[$i] = 'style="text-align:left;"';
                } else if (preg_match(self::ALIGNR, $aligns[$i])) {
                    $aligns[$i] = 'style="text-align:right;"';
                } else if (preg_match(self::ALIGNC, $aligns[$i])) {
                    $aligns[$i] = 'style="text-align:center;"';
                } else {
                    $aligns[$i] = '';
                }
            }

            for ($i = 0; $i < count($cols); $i++) {
                $this->writer->writeIndent("<th $aligns[$i]>\n");
                $this->writer->indent();
                $this->writer->writeIndent($this->parseInLine($cols[$i]));
                $this->writer->unindent();
                $this->writer->writeIndent("</th>\n");
            }

            $this->writer->unindent();
            $this->writer->writeIndent("</tr>\n");
            $this->writer->unindent();
            $this->writer->writeIndent("</thead>\n");

            return new EngineState(EngineState::TABLEH, -1, '', $aligns);
        }

        if (preg_match(self::TABLEL, $line)) {
            if ($state->state == EngineState::TABLEH) {
                $this->writer->writeIndent("<tbody>\n");
                $this->writer->indent();
            }

            $lastline = $state->table;
            if ($lastline != '') {
                $this->writer->writeIndent("<tr>\n");
                $this->writer->indent();

                $cols = array_values(array_filter(explode('|', $state->table), 'Gashmob\Mdgen\filter_array_empty'));
                for ($i = 0; $i < count($cols); $i++) {
                    $this->writer->writeIndent("<td {$state->aligns[$i]}>\n");
                    $this->writer->indent();
                    $this->writer->writeIndent($this->parseInLine($cols[$i]));
                    $this->writer->unindent();
                    $this->writer->writeIndent("</td>\n");
                }

                $this->writer->unindent();
                $this->writer->writeIndent("</tr>\n");
            }

            return new EngineState(EngineState::TABLEB, -1, $line, $state->aligns);
        }

        if ($state->state == EngineState::TABLEB) {
            $this->writer->writeIndent("<tr>\n");
            $this->writer->indent();

            $cols = array_values(array_filter(explode('|', $state->table), 'Gashmob\Mdgen\filter_array_empty'));
            for ($i = 0; $i < count($cols); $i++) {
                $this->writer->writeIndent("<td {$state->aligns[$i]}>\n");
                $this->writer->indent();
                $this->writer->writeIndent($this->parseInLine($cols[$i]));
                $this->writer->unindent();
                $this->writer->writeIndent("</td>\n");
            }

            $this->writer->unindent();
            $this->writer->writeIndent("</tr>\n");
            $this->writer->unindent();
            $this->writer->writeIndent("</tbody>\n");
        }
        $this->writer->unindent();
        $this->writer->writeIndent("</table>\n");

        return $this->parseInit($line);
    }

    const BOLD = /** @lang PhpRegExp */
        "/\*\*(.+?)\*\*/";
    const ITALIC = /** @lang PhpRegExp */
        "/\*(.+?)\*/";
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

        $line = trim($line);

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

            case EngineState::QUOTE:
                $this->writer->unindent();
                $this->writer->writeIndent("</blockquote>\n");
                break;

            case EngineState::TABLEB:
                $this->writer->unindent();
                $this->writer->writeIndent("</tbody>\n");
                $this->writer->unindent();
                $this->writer->writeIndent("</table>\n");
                break;
        }
    }

    /**
     * @param string $line
     * @param array $values
     * @return array
     */
    private function getIncludeValues($line, $values)
    {
        $keyValues = explode(',', $line);

        $regex = /** @lang PhpRegExp */
            '/^"(.*?)":(.*)$/';

        $result = [];
        foreach ($keyValues as $keyValue) {
            $matches = [];
            if (preg_match($regex, $keyValue, $matches)) {
                array_shift($matches);
            } else {
                $matches = explode(':', $keyValue);
                $matches[0] = substr(trim($matches[0]), 1, -1);
            }
            assert(count($matches) == 2);
            $key = trim($matches[0]);
            $value = $this->parseValue($this->getValue($values, $matches[1]));

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array $values
     * @param string $key
     * @return string
     */
    private function getValue($values, $key)
    {
        $keys = explode('.', $key);
        $current = $values;
        foreach ($keys as $key) {
            if (isset($current->$key)) {
                $current = $current->$key;
            } elseif (isset($current[$key])) {
                $current = $current[$key];
            } else {
                return $key;
            }
        }

        return $current;
    }

    /**
     * @param string $value
     * @return array|bool|float|int|string|null
     */
    private function parseValue($value)
    {
        if (is_numeric($value)) {
            return $value + 0;
        } else if (strtolower($value) == 'true') {
            return true;
        } else if (strtolower($value) == 'false') {
            return false;
        } else if (strtolower($value) == 'null') {
            return null;
        } else if (preg_match("/^\[.*]$/", $value)) {
            $array = explode(',', substr($value, 1, -1));
            return array_map("Gashmob\\YamlEditor\\YamlParser::parseValue", $array);
        } else if (preg_match("/^\".*\"$/", $value)) {
            return substr($value, 1, -1);
        } else {
            return $value;
        }
    }
}

/**
 * @param string $value
 * @return bool
 */
function filter_array_empty($value)
{
    return $value !== '';
}