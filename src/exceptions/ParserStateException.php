<?php

namespace Gashmob\Mdgen\exceptions;

use Exception;

class ParserStateException extends Exception
{
    public function __construct($msg = "Parser state exception", $line = false)
    {
        parent::__construct(!$line ? "$msg" : "$line: $msg");
    }
}