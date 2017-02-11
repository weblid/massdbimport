<?php
namespace Weblid\Massdbimport\Contracts;

interface arrayOutputInterface {
    
    protected $headers;

    protected $rows;

    public function getRows();

    public function getHeaders();

}