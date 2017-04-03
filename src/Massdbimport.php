<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Weblid\Massdbimport\Loggers\SessionLogger;

class Massdbimport
{ 
    /** 
     * Reference to the Eloquent model to map individual rows to.
     *
     * @var String
     * @access protected
     */
    protected $model;

    /** 
     * Columns used as indices to check for duplicates
     *
     * @var Array
     * @access protected
     */
    protected $uniqueColumns = [];

    /** 
     * Holds the dataset which is an array of all of the rows to 
     * import
     *
     * @var Array
     * @access protected
     */
    protected $rows = [];

    /** 
     * Holds the dataset which has been processed
     *
     * @var Array
     * @access protected
     */
    protected $processedRows = [];

    /** 
     * Holds reference to the log object
     *
     * @var Array
     * @access protected
     */
    protected $logger;

    /** 
     * Options
     *
     * @var Array
     * @access protected
     */
    protected $options = [
        "ifDuplicate" => "QUIT" // UPDATE, SKIP, RENAME or QUIT
    ];

    public function __construct()
    {
        $this->logger = new SessionLogger();
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /** 
     * Setter function for $this->rows
     *
     * @param Array $rows
     * @access public
     */
    public function getRows()
    {
        return $this->rows;
    }

    /** 
     * Get an array of the updated objects
     *
     * @param Array $rows
     * @access public
     */
    public function getProcessedRows()
    {
        return $this->processedRows;
    }

    /** 
     * Get the unqiue columns array
     *
     * @access public
     */
    public function getUniqueColumns()
    {
        return $this->uniqueColumns;
    }

     /** 
     * Add an array of the updated objects
     *
     * @param Array $rows
     * @access public
     */
    public function addProcessedRow($row)
    {
        $this->processedRows[] = $row;;
    }

    /** 
     * Setter function for $this->rows
     *
     * @param Array $rows
     * @access public
     */
    public function setRows(Array $rows)
    {
        $this->rows = $rows;
        return $this;
    }

    /** 
     * Getter function to get a option value
     *
     * @param String $option
     * @access public
     */
    public function getOption($option)
    {
        return strtoupper($this->options[$option]);
    }

    /** 
     * Setter function for $this->rows
     *
     * @param Array $rows
     * @access public
     */
    public function getModel()
    {
        return $this->model;
    }

    /** 
     * Setter function for $this->model
     *
     * @param String $model
     * @access public
     */
    public function model($model)
    {
        if(!new $model instanceof Model){
            dd("NOT A MODEL");
        } 

        $this->model = $model;
        return $this;
    }

    /** 
     * Facade interface for the 'ifDuplicate' option.
     *
     * @param String $action
     * @access public
     */
    public function ifDuplicate($action)
    {
        $this->options['ifDuplicate'] = $action;
        return $this;
    }

    /** 
     * Facade interface Add a column unique index
     *
     * @param Array $rows
     * @access public
     */
    public function unique($column)
    {
        $this->uniqueColumns[] = $column;
        return $this;
    }

    /** 
     * Facade interface Start looping through our dataset 
     * and preview the import
     *
     * @param Model $model
     * @access public
     */
    public function source($source)
    {
        if(strpos($source, ".csv") > -1){
            $csv = new \Weblid\Massdbimport\Importers\Csv($source);
            $this->rows = $csv->getRows();
            return $this;
        }
        else {
            dd("No CSV File");
        }
    }

    /** 
     * Start looping through our dataset and preview the import
     *
     * @access public
     */
    public function import()
    {
        if(empty($this->getRows())){
            return;
        }

        foreach($this->getRows() as $row){
            $row = new MassdbimportRow($this, $row);
            
            if($row->save()){
                $this->addProcessedRow($row);
            }
        }

        return $this;
    }

    /** 
     * Facade interface for log object
     *
     * @access public
     */
    public function logger()
    {
        return $this->logger;
    }
}

