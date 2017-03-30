<?php
namespace Weblid\Massdbimport\Loggers;

use Weblid\Massdbimport\Contracts\LoggerInterface as LoggerInterface;

class SessionLogger implements LoggerInterface {
    
    /**
     * Array of the actions performed
     *
     * @var Array
     * @access private
     */
    private $actions = [];

    /**
     * Getter for the actions array
     *
     * @return Array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Add an action tot he stack
     *
     * @var Array
     * @access private
     */
    private function addAction($action)
    {
        $this->actions[] = $action;
    }

    /**
     * Add a new record action to the stack
     *
     * @var Array
     * @access private
     */
    public function newRecord($row, $action="NEW", $oldrow=null)
    {
        $action = [
            "action" => $action,
            "model" => "Model",
            "data" => $row,
            "old_data" => $oldrow,
            "timestamp" => time(),
        ];
        $this->addAction($action);
    } 

    /**
     * Skipped a row when uploading
     *
     * @var Array
     * @access private
     */
    public function skipRecord($row, $oldrow=null)
    {
        $action = [
            "action" => "SKIP",
            "model" => "Model",
            "data" => $row,
            "old_data" => $oldrow,
            "timestamp" => time(),
        ];
        $this->addAction($action);
    } 

}