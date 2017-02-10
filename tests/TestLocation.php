<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;

class TestLocation extends Model
{
    public function parent()
    {
        return $this->belongsTo('Weblid\Massdbimport\TestLocation', 'test_location_id');
    }

    public function testShops()
    {
        return $this->hasMany('Weblid\Massdbimport\TestShop', 'test_location_id');
    }

    public function people()
    {
        return $this->belongsToMany('Weblid\Massdbimport\TestPerson');
    }
}
