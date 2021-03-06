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


class ImportOptionsTest extends TestCase
{
	public function setUp(){

		parent::setUp();
		
		Schema::dropIfExists('test_locations');
		Schema::create('test_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('test_location_id')->nullable();
            $table->string('slug')->unique();
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

	public function testSlugify(){

		// Flat import
		Massdbimport::model('\Weblid\Massdbimport\TestLocation')->setRows([
			[
                "slug"          => "USA",
				"name" 			=> "USA",
			],
			[
                "slug"          => "slugify(name)",
				"name"			=> "United Kingdom"
			]
		])->import();

	    $this->assertDatabaseHas('test_locations', ['name' => 'United Kingdom', 'slug' => 'UNITED_KINGDOM']);

	}

    public function testDuplicateOptionsUpdate(){

        // Check that the UPDATE option works on duplicates
        Massdbimport::model('\Weblid\Massdbimport\TestLocation')
        ->setRows([
            [
                "slug"          => "USA",
                "name"          => "USA",
            ],
            [
                "slug"          => "USA",
                "name"          => "Update"
            ]
        ])
        ->unique('slug')
        ->ifDuplicate("UPDATE")
        ->import();

        $this->assertDatabaseHas('test_locations', ['name' => 'Update', 'slug' => 'USA']);
        $this->assertDatabaseMissing('test_locations', ['name' => 'USA', 'slug' => 'USA']);

    }

    public function testDuplicateOptionsSkip(){

        // Check that the SKIP option works on duplicates
        Massdbimport::model('\Weblid\Massdbimport\TestLocation')
        ->setRows([
            [
                "slug"          => "USA",
                "name"          => "USA",
            ],
            [
                "slug"          => "USA",
                "name"          => "Update"
            ]
        ])
        ->unique('slug')
        ->ifDuplicate("SKIP")
        ->import();

        $this->assertDatabaseHas('test_locations', ['name' => 'USA', 'slug' => 'USA']);
        $this->assertDatabaseMissing('test_locations', ['name' => 'Update', 'slug' => 'USA']);

    }


    public function testDuplicateOptionsRename(){

        // Check that the UPDATE option works on duplicates
        Massdbimport::model('\Weblid\Massdbimport\TestLocation')
        ->setRows([
            [
                "slug"          => "USA",
                "name"          => "USA",
            ],
            [
                "slug"          => "USA",
                "name"          => "USA2"
            ]
        ])
        ->unique('slug')
        ->ifDuplicate("RENAME")
        ->import();

        $this->assertDatabaseHas('test_locations', ['name' => 'USA', 'slug' => 'USA']);
        $this->assertDatabaseHas('test_locations', ['name' => 'USA2']);

    }


    public function tearDown(){
		Schema::dropIfExists('test_locations');
		Schema::dropIfExists('test_people');
		Schema::dropIfExists('test_shops');
		Schema::dropIfExists('test_location_test_person');
	}
}