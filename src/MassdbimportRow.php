<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;

class MassdbimportRow {

    protected $skipSave = false;

    /**
     * A new row specific instance of the Eloquent model
     *
     * @access protected
     */
    protected $model;

    /**
     * A reference back to the parent object
     *
     * @access protected
     */
    protected $parent;

    /**
     * An array of columns in "key/val" format
     *
     * @access protected
     */
    protected $columns;

    /**
     * An array of the relations to attach after the save
     * Typically used for toMany relations
     *
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

    /**
     * Returns instance of parent Massdbimport object
     *
     * @access public
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns instance of current model
     *
     * @access public
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Returns instance of current columns
     *
     * @access public
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Returns instance of current model
     *
     * @param String $relation 
     * @param Array $ids
     *
     * @access public
     */
    public function getPostSaveRelations()
    {
        return $this->postSaveRelations;
    }

    /**
     * Returns instance of current model
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
     * @access public
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
            $skip = false;
            if($this->isUniqueKey($key) && $this->isDuplicate($key, $value)){
                switch($this->getParent()->getOption('ifDuplicate'))
                {
                    case "UPDATE":
                        $oldRow = $this->model->where($key, $value)->first();
                        $modelTemplate = $this->parent->getModel();
                        $this->model = $modelTemplate::find($oldRow->id);
                    break;
                    case "SKIP":
                        $this->skipSave = true;
                    break;
                    case "RENAME":
                        $this->model->$key = $key . '_' . time(); 
                        $skip = true;
                    break;
                }
            }

            if($skip)
                continue;

            if($parsedColumn->getRelationType() == "BelongsToMany"){
                //dd($this->getPostSaveRelations());
            }
            else if($parsedColumn->isRelationalKey()){
                $this->model->$key()->associate( $value[0] );
            }
            else {
                $this->model->$key = $value;
            }
        }
    }

    /**
     * Invokes model's save method to save our parsed row
     */
    public function save()
    {
        if($this->skipSave){
            return true;
        }

        if($this->model->save()){
            $this->doPostSaveRelations();
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