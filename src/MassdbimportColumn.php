<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

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
     * Getter method to retrieve original value
     */
    public function getModel()
    {
        return $this->row->getModel();
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
     * Check if the value is in a 'relation' column. If it is then we
     * search for the referenced object(s). If it's a normal column, 
     * just return the value. 
     */
    protected function parse()
    {
        if($this->isRelationalKey()){
            $this->parsedValue = $this->makeRelationInstruction();
        } 
        else {
            $this->parsedValue = $this->parseFlatValue($this->value);
        } 
    }
    
    /**
     * Checks a cell value for functions and executes
     * function on value to return the new value
     *
     * @param String $value
     *
     * @return $value
     */
    protected function parseFlatValue($value)
    {
        if($parseParts = $this->getParseFunction($value)){
            if($parseParts[0] == "slugify"){
                $column = $parseParts[1];
                $model = $this->row->getColumns();
                $value = strtoupper(str_slug($model[$column], '_'));
            }
            if($parseParts[0] == "imagify"){
                $imageUrl = $parseParts[1];
                $size = getimagesize($imageUrl);
                $extension = image_type_to_extension($size[2]);
                $filename = time() . $extension;
                $file = Storage::put('/public/imported/'.$filename, file_get_contents($imageUrl), 'public');
                $url = 'public/imported/'.$filename;
            
                $value = $url;
            }
        }

        if($value == ""){
            $value = null;
        }
        
        return $value;
    }


    /** 
     * Disect the key column and analise the relation: and the :column name
     * then get the associated models and put them in an array
     *
     * @return Array 
     */
    protected function makeRelationInstruction()
    {
        $parts = $this->keyRelationParts();

        $this->parsedKey = $relation = $parts['relation'];
        
        $relationType = $this->setRelationType($relation);

        $values = array_filter($this->getValueAsArray());
   
        if(empty($values) || !$values){
            return null;
        }
        
        $parts['relation_type'] = $relationType;
        $parts['data'] = $values;
   
        return $parts;
        
    }

    /**
     * Gets relation type from a key
     *
     * @param String $relation
     * 
     * @return String 
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
     * Looks at a value for () to indicate a function. If
     * found returns an array of function and value
     *
     * @param String $value
     *
     * @return Array
     */
    protected function getParseFunction($value)
    {
        if(strpos($value, "(") > 0 && strpos($value, ")") == strlen($value)-1){

            $start = strpos($value, "(");
            $func = substr($value, 0,$start);
            preg_match("#\((.*?)\)#", $value, $matches);
            $value = $matches[1];
            
            return [$func, $value];
        }

        return false;
    }



}