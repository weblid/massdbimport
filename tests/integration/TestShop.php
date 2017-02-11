<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;

class TestShop extends Model
{
    public function testLocation()
    {
        return $this->belongsTo('Weblid\Massdbimport\TestLocation', 'test_location_id');
    }

}
