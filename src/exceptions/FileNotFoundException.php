<?php

namespace Gashmob\Mdgen\exceptions;

use Exception;

class FileNotFoundException extends Exception
{
    /**
     * @var string The filename that was not found
     */
    private $filename;

    /**
     * @param string $filename
     */
    public function __construct($filename)
    {
        parent::__construct();

        $this->filename = $filename;
    }

    public function __toString()
    {
        return "File not found: {$this->filename}";
    }
}