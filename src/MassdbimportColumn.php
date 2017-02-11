<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;

class MassdbimportColumn {

    /**
     * Reference to the parent Row object
     *
     * @access protected
     */
    public $row;

    /**
     * The name of the column key before any processing
     *
     * @access protected
     */
    protected $key;

    /**
     * The value of the column before any processing
     *
     * @access protected
     */
    protected $value;

    /**
     * The name of the column key after processing
     *
     * @access protected
     */
    protected $parsedKey;

    /**
     * The value of the column after processing
     *
     * @access protected
     */
    protected $parsedValue;

    /**
     * If relation column - what kind of column
     *
     * @access protected
     */
    protected $relationType;

    /**
     * Inject our boot variables. 
     *
     * @param MassdbimportRow $row
     * @param String $key
     * @param String $value
     *
     * @access protected
     */
    public function __construct(MassdbimportRow $row, $key, $value)
    {
        $this->key = $key;
        $this->value = $value;

        $this->parsedKey = $key;
        $this->parsedValue = $value;

        $this->row = $row;

        $this->parse();
    }

    /**
     * Getter method to retrieve original value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Getter method to retrieve original key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Getter method to retrieve parsed key
     */
    public function getParsedKey()
    {
        return $this->parsedKey;
    }

    /**
     * Getter method to retrieve parsed value
     */
    public function getParsedValue()
    {
        return $this->parsedValue;
    }

    /**
     * Getter method for $this->relationType
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Setter method for $this->relationType
     */
    private function setRelationType($relation)
    {
        $relationType = get_class($this->row->getModel()->$relation());
        $parts = explode("\\", $relationType);
        $this->relationType = end($parts);
        return $this->relationType;
    }

    /**
     * Checks the column key for any ":" characters which intruct 
     * that this is a relation column.
     *
     * @return bool
     */
    public function isRelationalKey()
    {
        if(strpos($this->key, ':') > 1){
            return true;
        } 
        return false;
    }

    /**
     * Splits a relation key (with ":") into two parts. First part is
     * the relation method and the second part of the column on the related
     * table. 
     *
     * @return Array
     */
    protected function keyRelationParts()
    {
        $parts = explode(':', $this->key);
        $relation = $parts[0];
        $column = $parts[1];

        return ["relation" => $relation, "column" => $column];
    }

    /**
     * Look for "|" seperator in the value which indicated a manyToMany relation.
     * Also fires single values back in an array.
     *
     * @return Array
     */
    protected function getValueAsArray()
    {
        if(strpos($this->value, '|') > 1){
            $values = array_filter(explode("|", $this->value));
        } else {
            $values = [$this->value];
        }
        return $values;
    }

    /** 
     * Check if the value is in a 'relation' column. If it is then we
     * search for the referenced object(s). If it's a normal column, 
     * just return the value. 
     */
    protected function parse()
    {
        if($this->isRelationalKey()){
            $this->parsedValue = $this->getRelatedObjectsFromKey();
        } 
        else {
            $this->parsedValue = $this->value;
        } 
    }

    /** 
     * Disect the key column and analise the relation: and the :column name
     * then get the associated models and put them in an array
     *
     * @return Array 
     */
    protected function getRelatedObjectsFromKey()
    {
        $parts = $this->keyRelationParts();

        $this->parsedKey = $relation = $parts['relation'];
        
        $relationType = $this->setRelationType($relation);

        if($relationType == "BelongsToMany"){
            $values = $this->getValueAsArray();
            
            $relatedObjects = [];
        
            foreach($values as $objectLink){
                $relation = $parts['relation'];
                $relatedModel = $this->row->getModel()->$relation()->getRelated();
                $relatedObjects[] = $this->getRelationRecord($relatedModel, $parts['column'], $objectLink);
            }

            $relatedIds = array_map(function($o) { return $o->id; }, $relatedObjects);

            $this->row->pushPostSaveRelation($relation, $relatedIds);

            return $relatedIds;
        }
        else {
            $values = $this->getValueAsArray();

            $relatedObjects = [];
        
            foreach($values as $objectLink){
                $relation = $parts['relation'];
                $relatedModel = $this->row->getModel()->$relation()->getRelated();
                $relatedObjects[] = $this->getRelationRecord($relatedModel, $parts['column'], $objectLink);
            }

            return $relatedObjects; 
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

}