<?php

namespace Weblid\Massdbimport;

class Massdbimport
{ 
	/** 
	 * Reference to the Eloquent model to map individual
	 * rows to.
	 *
	 * @access protected
	 */
	protected $model = '\App\Company';

	/** 
	 * Holds the dataset which is an array of all of the 
	 * rows to import
	 *
	 * @access protected
	 */
	protected $rows = [
		[
			"name" 			=> "Company",
			"location_id" 	=> 2,
			"slug" 			=> "COMPANY",
			"created_by"	=> 1,
			"updated_by"	=> 1,
		]
	];

	/** 
	 * Setter function for $this->model
	 *
	 * @param Model $model
	 * @access public
	 */
	public function model(Model $model){
		$this->model = $model;
	}

	/** 
	 * Start looping through our dataset and preview the import
	 *
	 * @param Model $model
	 * @access public
	 */
    public function import(){
  	
    	if(empty($this->rows)){
    		return;
    	}

    	foreach($this->rows as $row){
    		$this->rowToModel($row);
    	}

    }

    protected function rowToModel($row){
    	if(!$this->model){
    		dd("NO MODEL");
    	}
    	
    	$model = new $this->model();

    	foreach($row as $key => $column){
    		$this->column($model, $key, $column);
    	}

    	dd($model);
    }

    protected function column($model, $key, $column){
    	$model->$key = $column;
    	return $model;
    }


}
