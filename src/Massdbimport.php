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
			"name" 			=> "Location",
			"slug" 			=> "DEP1",
			"university:slug" 	=> "LJMU",
			"created_by"	=> 1,
			"updated_by"	=> 1,
		],
		[
			"name" 			=> "Location2",
			"slug" 			=> "DEP12",
			"university:slug" 	=> "LJMU",
			"created_by"	=> 1,
			"updated_by"	=> 1,
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

    		if($parsedColumn->isRelationalKey()){
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
	}
}

class MassdbimportColumn {

	/**
	 * Reference to the parent Row object
	 *
	 * @access protected
	 */
	protected $row;

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
	protected function valueAsArray()
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

		$this->parsedKey = $parts['relation'];
		
		$values = $this->valueAsArray();

		$relatedObjects = [];
	
		foreach($values as $objectLink){
			$relation = $parts['relation'];
			$relatedModel = $this->row->getModel()->$relation()->getRelated();
			$relatedObjects[] = $this->getRelationRecord($relatedModel, $parts['column'], $objectLink);
		}

		return $relatedObjects;	
    }

    /*
    protected function associateDerivedRelations(){

    	if(strpos(get_class($this->row->$relation()), "BelongsToMany") > -1){
			$relationLinkMethod = 'attach';
			$objectsToLink = array_column($relatedObjects, 'id');
		} else {
			$relationLinkMethod = 'associate';
			$objectsToLink = $relatedObjects;
		}

		if(!empty($objectsToLink)){	
    		return $this->value = $objectsToLink;
    	} 
    }
	*/

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
