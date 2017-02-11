<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;

class Massdbimport
{ 
    /** 
     * Reference to the Eloquent model to map individual rows to.
     *
     * @access protected
     */
    protected $model;

    /** 
     * Holds the dataset which is an array of all of the rows to 
     * import
     *
     * @access protected
     */
    protected $rows = [
        [
            "name"             => "Location",
            "slug"             => "DEP1",
            "university:slug"     => "LJMU",
            "created_by"    => 1,
            "updated_by"    => 1,
        ],
        [
            "name"             => "Location2",
            "slug"             => "DEP12",
            "university:slug"     => "LJMU",
            "created_by"    => 1,
            "updated_by"    => 1,
        ]
    ];

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
     * Start looping through our dataset and preview the import
     *
     * @param Model $model
     * @access public
     */
    public function import()
    {
        if(empty($this->getRows())){
            return;
        }

        foreach($this->getRows() as $row){
            $row = new MassdbimportRow($this, $row);
            $row->save();
        }
    }
}

