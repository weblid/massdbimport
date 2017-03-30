<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;

class MassdbimportRow {

    protected $skipSave = false;

    /**
     * A new row specific instance of the Eloquent model
     *
     * @var Model
     * @access protected
     */
    protected $model;

    /**
     * A new row specific instance of the Eloquent model
     *
     * @var Model
     * @access protected
     */
    protected $prevModel = null;

    /**
     * A reference back to the parent object
     *
     * @var Massdbimport
     * @access protected
     */
    protected $parent;

    /**
     * An array of columns in "key/val" format
     *
     * @var Array
     * @access protected
     */
    protected $columns;

    /**
     * An array of the relations to attach after the save
     * Typically used for toMany relations
     *
     * @var Array
     * @access protected
     */
    protected $postSaveRelations = [];

    /**
     * Constructor method sets up dependencies
     *
     * @param Massdbimport $parent
     * @param Array $columns
     *
     * @access protected
     */
    public function __construct(Massdbimport $parent, $columns)
    {
        $this->columns = $columns;
        $this->parent = $parent;
        $modelTemplate = $parent->getModel();
        $this->model = new $modelTemplate;
        $this->parseRow();
    }

    public function log()
    {
        return $this->getParent()->getLogger();
    }
    /**
     * Returns instance of parent Massdbimport object
     *
     * @return Massdbimport
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns instance of current model
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Returns instance of previous (row in db) model
     *
     * @return Model
     */
    public function getPrevModel()
    {
        return $this->prevModel;
    }

    /**
     * Returns instance of current columns
     *
     * @return Array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Gets a list of the relations to save after 
     * the row's saved
     *
     * @return Array
     */
    public function getPostSaveRelations()
    {
        return $this->postSaveRelations;
    }

    /**
     * Sets a flag to tell the row not to save to db
     *
     * @return Array
     */
    public function skipSave()
    {
        return $this->skipSave = true;;
    }

    /**
     * Adds a relation to the stack of relations to attach
     * after the row is saved
     *
     * @param String $relation 
     * @param Array $ids
     *
     * @access public
     */
    public function pushPostSaveRelation($relation, $ids)
    {
        $this->postSaveRelations[$relation] = $ids;
        return $this->postSaveRelations;
    }

    /**
     * Checks if a key is a designated unique column
     *
     * @param String $key 
     *
     * @return Bool
     */
    public function isUniqueKey($key)
    {
        if(in_array($key, $this->parent->getUniqueColumns())){
            return true;
        }
        return false;
    }

    /**
     * Checks if there is already a record in this model with the
     * given key and value
     *
     * @param String $key
     * @param String $value
     *
     * @return Bool
    */
    public function isDuplicate($key, $value)
    {
        $model = $this->getParent()->getModel();
        $model = new $model;
        $count = $model->where($key, $value)->count();

        return $count > 0 ? true : false;
    }

    /**
     * Loops through columns and gets keys and values 
     */
    protected function parseRow()
    {
        foreach($this->columns as $key => $value)
        {
            $parsedColumn = new MassdbimportColumn($this, $key, $value);
            $key = $parsedColumn->getParsedKey();
            $value =  $parsedColumn->getParsedValue();

            if($this->isUniqueKey($key) && $this->isDuplicate($key, $value)){
                
                $this->prevModel = $this->getModelRow($key, $value);

                switch($this->getParent()->getOption('ifDuplicate'))
                {
                    case "UPDATE":
                        $this->setCurrentRowAsDifferentRecord($key, $value);
                    break;
                    case "SKIP":
                        $this->skipSave();
                    break;
                    case "RENAME":
                        $this->renameUniqueCell($key);
                        continue 2;
                    break;
                }
            }

            $this->handleModelAssign($parsedColumn);
        }
    }

    /**
     * Takes the value of a cell and appends a timestamp to it to avoid 
     * duplicates
     *
     * @param String $key - (model atrribute)
     *
     * @return null
     */
    private function renameUniqueCell($key)
    {
        $this->model->$key = $key . '_' . time(); 
    }

    /**
     * Loads a database row into the model from given column key and 
     * value - used for update function
     *
     * @param String $key 
     * @param String $value 
     *
     * @return null
     */
    private function setCurrentRowAsDifferentRecord($key, $value)
    {
        $this->model = $this->getModelRow($key, $value);
    }

    /**
     * Get's row from db from key and value
     *
     * @param String $key 
     * @param String $value 
     *
     * @return null
     */
    private function getModelRow($key, $value)
    {
        $oldRow = $this->model->where($key, $value)->first();
        $modelTemplate = $this->parent->getModel();
        return $modelTemplate::find($oldRow->id);
    }

    /**
     * Assigns the parsed Column cell to the model attribute 
     *
     * @param MassdbimportColumn $cell 
     * 
     * @return null
     */
    private function handleModelAssign(MassdbimportColumn $cell)
    {
        $key = $cell->getParsedKey();
        $value =  $cell->getParsedValue();

        if($cell->getRelationType() == "BelongsToMany")
        {
            $this->pushPostSaveRelation($key, $value);
        }
        else if($cell->isRelationalKey())
        {
            $this->model->$key()->associate( $value[0] );
        }
        else 
        {
            $this->model->$key = $value;
        }
    }

    /**
     * Invokes model's save method to save our parsed row
     */
    public function save()
    {
        if($this->skipSave){
            $this->log()->skipRecord($this->model, $this->prevModel);
            return true;
        }

        $this->model->id ? $action = "UPDATE" : $action = "NEW";

        if($this->model->save()){
            $this->doPostSaveRelations();
            $this->log()->newRecord($this->model, $action, $this->getPrevModel());
            return true;
        }
        return false;
    }

    /**
     * Analyze the postSaveRelations proerpty and attach
     * and ManyToMany relations
     */
    private function doPostSaveRelations()
    {
        if(empty($this->getPostSaveRelations()))
            return;

        foreach($this->getPostSaveRelations() as $relation => $ids){
            $this->getModel()->$relation()->detach();
            $this->getModel()->$relation()->attach($ids);
        }
    }
}