<?php

namespace Gashmob\Mdgen\exceptions;

use Exception;

class ParserStateException extends Exception
{
    public function __construct()
    {
        parent::__construct("Parser state exception");
    }
}