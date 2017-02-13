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


class FileToDbTest extends TestCase
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

	public function testCsvToDb(){
		Massdbimport::model('Weblid\Massdbimport\TestLocation')->source(__DIR__.'/locationsSimple.csv')->import();

        $this->assertDatabaseHas('test_locations', ['name' => 'UK']);
	}


    public function tearDown(){
		Schema::dropIfExists('test_locations');
		Schema::dropIfExists('test_people');
		Schema::dropIfExists('test_shops');
		Schema::dropIfExists('test_location_test_person');
	}
}