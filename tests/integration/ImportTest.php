<?php
namespace Weblid\Massdbimport;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Tests\TestCase;
use Weblid\Massdbimport\Facades\Massdbimport;
use Weblid\Massdbimport\TestLocation;
use Weblid\Massdbimport\TestPerson;
use Weblid\Massdbimport\TestShop;


class ImportTest extends TestCase
{
	public function setUp(){

		parent::setUp();
		
		Schema::dropIfExists('test_locations');
		Schema::create('test_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('test_location_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::dropIfExists('test_people');
		Schema::create('test_people', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        
        Schema::dropIfExists('test_shops');
		Schema::create('test_shops', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('test_location_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::dropIfExists('test_location_test_person');
		Schema::create('test_location_test_person', function (Blueprint $table) {
            $table->increments('test_location_id');
            $table->integer('test_person_id');
        });
		
	}

	public function testImportsAndRelations(){

		// Flat import
		Massdbimport::model('\Weblid\Massdbimport\TestLocation')->setRows([
			[
				"name" 			=> "USA",
			],
			[
				"name"			=> "UK"
			]
		])->import();

		
		// Self referencing relation import
		Massdbimport::model('\Weblid\Massdbimport\TestLocation')->setRows([
			[
				"name" 			=> "Merseyside",
				"parent:name" 	=> "UK",
			]
		])->import();
		
		// Cross table relation test
		Massdbimport::model('\Weblid\Massdbimport\TestShop')->setRows([
			[
				"name" 			=> "Aldi",
				"testLocation:name" => "USA"
			]
		])->import();
		
		// Test many to many
		
		Massdbimport::model('\Weblid\Massdbimport\TestPerson')->setRows([
			[
				"name" 			=> "Jon Doe",
				"testLocations:name" => "USA|UK"
			]
		])->import();
		
		$this->assertDatabaseHas('test_locations', ['name' => 'USA']);
		$this->assertDatabaseHas('test_locations', ['name' => 'Merseyside', 'test_location_id' => 2]);
		$this->assertDatabaseHas('test_shops', ['name' => 'Aldi', 'test_location_id' => 1]);
        $this->assertDatabaseHas('test_location_test_person', ['test_location_id' => 1, 'test_person_id' => 1]);
        $this->assertDatabaseHas('test_location_test_person', ['test_location_id' => 2, 'test_person_id' => 1]);

	}


    public function tearDown(){
		Schema::dropIfExists('test_locations');
		Schema::dropIfExists('test_people');
		Schema::dropIfExists('test_shops');
		Schema::dropIfExists('test_location_test_person');
	}
}