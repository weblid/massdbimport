<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;

class MassdbimportRow {

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
     * Returns instance of parent Massdbimport object
     *
     * @access protected
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns instance of current model
     *
     * @access protected
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Returns instance of current model
     *
     * @param String $relation 
     * @param Array $ids
     *
     * @access protected
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
     * @access protected
     */
    public function pushPostSaveRelation($relation, $ids)
    {
        $this->postSaveRelations[$relation] = $ids;
        return $this->postSaveRelations;
    }

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
     * Loops through columns and gets keys and values 
     */
    protected function parseRow()
    {
        foreach($this->columns as $key => $value)
        {
            $parsedColumn = new MassdbimportColumn($this, $key, $value);

            $key = $parsedColumn->getParsedKey();
            $value =  $parsedColumn->getParsedValue();

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
        $this->model->save();
        $this->doPostSaveRelations();
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