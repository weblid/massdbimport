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
     * An array of the relations to attach after the save
     *
     * @var Array
     * @access protected
     */
    protected $postSaveRelations = [];

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
        "ifDuplicate" => "QUIT", // UPDATE, SKIP, RENAME or QUIT
        "ifRelationError" => "QUIT" // IGNORE, SKIP or QUIT
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
     * Gets a list of the relations to save after 
     * all the items saved
     *
     * @return Array
     */
    public function getPostSaveRelations()
    {
        return $this->postSaveRelations;
    }

    /**
     * Adds a relation to the stack of relations to attach
     * after the rows are saved
     *
     * @param String $relation 
     * @param Array $ids
     *
     * @access public
     */
    public function pushPostSaveRelation($row, $instructions)
    {
        $this->postSaveRelations[] = [
            "row" => $row,
            "instructions" => $instructions
        ];

        return $this->postSaveRelations;
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
     * Facade interface for the 'ifRelationError' option.
     *
     * @param String $action
     * @access public
     */
    public function ifRelationError($action)
    {
        $this->options['ifRelationError'] = $action;
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

        $this->doPostSaveRelations();

        return $this;
    }

    /**
     * Analyze the postSaveRelations proerpty and attach all
     * the reltions according tot he array instructions
     */
    private function doPostSaveRelations()
    {

        if(empty($this->getPostSaveRelations()))
            return;

        foreach($this->getPostSaveRelations() as $instruct){

            if(empty($instruct['instructions']))
                continue;

            if($instruct['instructions']['relation_type'] == "BelongsToMany"){
                $idsToAttach = [];
                foreach($instruct['instructions']['data'] as $relationId){

                    $relation = $instruct['instructions']['relation'];
                    $relatedModel = $instruct['row']->$relation()->getRelated();

                    if($obj)
                        $obj = $this->getRelationRecord($relatedModel, $instruct['instructions']['column'], $relationId);

                    $idsToAttach[] = $obj->id;
                }
                $instruct['row']->$relation()->attach($idsToAttach);
            }
            else {
                $relation = $instruct['instructions']['relation'];
                $relatedModel = $instruct['row']->$relation()->getRelated();
                
                $obj = $this->getRelationRecord($relatedModel, $instruct['instructions']['column'], $instruct['instructions']['data'][0]);
                
                if($obj){
                    $instruct['row']->$relation()->associate($obj);
                    $instruct['row']->save();
                }
            }
        }
    }

    /**
     * Get the related record from a given column and value to search for
     *
     * @param Model $model Instance to search for related record 
     * @param String $column 
     * @param Mixed $value to look for
     */
    protected function getRelationRecord(Model $relation, $column, $value)
    {
        return $relation->where($column, $value)->first();
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

