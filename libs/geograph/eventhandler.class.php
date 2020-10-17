<?php

require_once('geograph/eventprocessor.class.php');
class EventHandler
{
	var $db;
	var $processor;

	/**
	 * Execute a SQL query, producing a warning if takes over 1 second
	 * @access private
	 */
	function Execute($sql, $slow = 1) {
		$db=&$this->_getDB();

		$start = microtime(true);
		$r = $db->Execute($sql) or $this->_output(1, "$sql;\n ".$db->ErrorMsg());
		$end = microtime(true);

		$this->_output(($end-$start > $slow)?2:4, sprintf("%s; #... took %.3f seconds, %d affected rows\n", $sql, $end-$start, $db->Affected_Rows()));
		return $r;
	}

	/**
	 * Output a message, have our own version, rather than using processor directly, as might not always be available
	 * @access private
	 */
	function _output($level, $text) {
		if (!empty($this->processor)) {
			$this->processor->_output($level, $text);

		//if dont have a processer, will only output error/warnings (1/2)
		} elseif($level <= 2) {
			print date('r')." [$level] $text\n";
		}
	}

	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (empty($this->db) || !is_object($this->db))
			$this->db=GeographDatabaseConnection(false);

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


