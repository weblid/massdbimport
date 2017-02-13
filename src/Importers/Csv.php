<?php
namespace Weblid\Massdbimport\Importers;

use Weblid\Massdbimport\Contracts\ArrayOutputInterface as ArrayOutputInterface;

class Csv implements ArrayOutputInterface {
    
    /**
     * File pointer
     *
     * @access private
     */
    private $fp;

    /**
     * The original, untouched raw data from the 
     * CSV file. 
     *
     * @access protected 
     */
    protected $sourcePath; 

    /**
     * Array of headers
     *
     * @access protected 
     */
    protected $headers;

    /**
     * The parsed array
     *
     * @access protected 
     */
    protected $rows = [];

    /**
     * Set the full path to the file to parse
     *
     * @param String $path 
     */
    public function setSourcePath($path)
    {
        $this->sourcePath = $path;
    }

    /**
     * Get the full path to the file to parse
     *
     * @access public 
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    /**
     * Set the headers which will become the keys
     *
     * @param Array $headers  
     */
    protected function setHeaders(Array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Get the headers which will become the keys
     *
     * @access public 
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds a row of values
     *
     * @param Array $row  
     */
    protected function addDataRow(Array $row)
    {
        array_push($this->rows, $row);
    }

    /**
     * Gets the rows of values 
     *
     * @access public 
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Constructor parses data on initialisation
     *
     * @param String $sourceFile - Path+file
     */
    public function __construct($sourceFile)
    {
        if(!file_exists($sourceFile)){
            return false;
        }

        $this->setSourcePath($sourceFile);
        $this->fp = fopen($sourceFile, "r"); 
        
        $this->parseHeaders();
        $this->parseContent();
        
    }

    /**
     * Destructor closes file pointer
     */
    public function __destruct()
    {
        fclose($this->fp);
    }

    /**
     * Turns the values of the first row into the headers
     */
    private function parseHeaders()
    {
        $this->setHeaders(array_filter(fgetcsv($this->fp))); 
    }

    /**
     * Traverse through all rows > 1 and parses anything not empty
     * to the rows property
     */
    private function parseContent()
    {   
        while ( ($row = fgetcsv($this->fp) ) !== FALSE ) {
            array_splice($row, count($this->getHeaders())); 

            $arr_empty = true;
            foreach ($row as $el) {
                if (!empty($el)) {
                    $arr_empty = false;
                }
            }

            if(!$arr_empty){
                $headerKeys = $this->getHeaders();
                $rowWithHeaderKeys = array_combine($headerKeys, $row );
                $this->addDataRow($rowWithHeaderKeys);
            }
        }
    }

}