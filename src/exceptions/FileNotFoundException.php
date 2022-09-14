<?php

namespace Gashmob\Mdgen\exceptions;

use Exception;

class FileNotFoundException extends Exception
{
    /**
     * @param string $filename
     */
    public function __construct($filename)
    {
        parent::__construct("File not found: $filename");
    }
}