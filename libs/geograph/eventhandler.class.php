<?php

require_once('geograph/eventprocessor.class.php');
class EventHandler
{
	var $db;
	var $processor;
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');  
		return $this->db;
	}

	/**
	 * set stored db object
	 * @access private
	 */
	function _setDB(&$db)
	{
		$this->db=$db;
	}

	/**
	 * set stored EventProcessor object
	 * @access private
	 */
	function _setProcessor(&$processor)
	{
		$this->processor=$processor;
	}

	/**
	* Override to process event.
	* 
	* Function should return false in the event of failure and
	* would like to be scheduled for another run. Note that handlers
	* for the same event that ran successfully will not be re-run
	*/
	function processEvent(&$event)
	{
		return false;
	}


}


