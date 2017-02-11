<?php

namespace Weblid\Massdbimport;

use Illuminate\Database\Eloquent\Model;

class TestPerson extends Model
{
    public function testLocations()
    {
        return $this->belongsToMany('Weblid\Massdbimport\TestLocation');
    }

}
