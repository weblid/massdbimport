<?php
namespace Weblid\Massdbimport\Contracts;

interface LoggerInterface {
    
    public function newRecord($row);

    public function skipRecord($row);
/*
    public function addRowUpdate($row);

    public function addRowDelete($row);

    public function addRowError($row);

    public function addRelation($relation);
    */

}