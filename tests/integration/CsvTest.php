<?php
namespace Weblid\Massdbimport;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Tests\TestCase;


class CsvTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();
	}

	public function testCsvParser()
	{
		$csv = new \Weblid\Massdbimport\Importers\Csv(__DIR__.'/locationsSimple.csv');
		$rows = $csv->getRows();
		
		$this->assertContains('locationsSimple.csv', $csv->getSourcePath());

		$this->assertContains('name', $csv->getHeaders());
		
		$this->assertContains('UK', $rows[0]);

		$this->assertEquals(3, count($csv->getRows()));
		
	}
	


}