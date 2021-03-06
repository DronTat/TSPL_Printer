<?php

namespace tspl_printer\PrinterConnectors;

use Exception;

/**
 * PrintConnector for passing print data to a file.
 */
class FilePrintConnector implements PrintConnector
{
    /**
     * @var resource $fp
     *  The file pointer to send data to.
     */
    protected $fp;

    /**
     * Construct new connector, given a filename
     *
     * @param string $filename
     * @throws Exception
     */
    public function __construct($filename)
    {
        $this -> fp = fopen($filename, "wb+");
        if ($this -> fp === false) {
            throw new Exception("Cannot initialise FilePrintConnector.");
        }
    }

    public function __destruct()
    {
        if ($this -> fp !== false) {
            trigger_error("Print connector was not finalized. Did you forget to close the printer?", E_USER_NOTICE);
        }
    }

    /**
     * Close file pointer
     */
    public function finalize()
    {
        if ($this -> fp !== false) {
            fclose($this -> fp);
            $this -> fp = false;
        }
    }

    /**
     * @param string $len
     * @return bool|string
     * @throws Exception
     */
    public function read($len)
    {
        if ($this -> fp === false) {
            throw new Exception("PrintConnector has been closed, cannot read input.");
        }
        return fread($this -> fp, $len);
    }

    /**
     * Write data to the file
     *
     * @param string $data
     * @throws Exception
     */
    public function write($data)
    {
        if ($this -> fp === false) {
            throw new Exception("PrintConnector has been closed, cannot send output.");
        }
        fwrite($this -> fp, $data);
    }
}
