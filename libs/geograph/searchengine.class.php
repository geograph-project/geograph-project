<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */



/**
* Provides the SearchEngine class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/


/**
* SearchEngine
*
* 
* @package Geograph
*/
class SearchEngine
{
	var $db=null;
	
	var $query_id;
	
	/**
	* text representing this search
	*/
	var $criteria;
	
	
	
	/**
	* the results
	*/
	var $results;
	
	var $resultCount = 0;
	var $numberOfPages;
	var $currentPage;
	
	var $islimited = false;
	
	/**
	* setup the values
	*/
	function SearchEngine($query_id)
	{
		$this->query_id = $query_id;

		$db=$this->_getDB();
		
		$query = $db->GetRow("SELECT * FROM queries WHERE id = $query_id");
	
		//todo surely there a better way to do this in one line...
			//			$this->criteria = new ${"SearchCriteria_".$row['searchclass']}();
		switch ($query['searchclass']) {
					
			case "Postcode":
				$this->criteria = new SearchCriteria_Postcode($query['q']);
				break;
			case "GridRef":
				$this->criteria = new SearchCriteria_GridRef($query['q']);
				break;
			case "County":
				$this->criteria = new SearchCriteria_County($query['q']);
				##$this->criteria->setByCounty($query['searchq']);
				break;
			case "Placename":
				$this->criteria = new SearchCriteria_Placename($query['q']);
				break;
			case "Random":
				$this->criteria = new SearchCriteria_Random($query['q']);
				break;
			case "Text":
				$this->criteria = new SearchCriteria_Text($query['q']);
				break;
		}
	
		$this->criteria->_initFromArray($query);
	

  
	} 

	function Execute($pg) 
	{
		$db=$this->_getDB();
		
		
		$sql_fields = "";
		$sql_order = "";
		$sql_where = "";
		
		$this->criteria->getSQLParts($sql_fields,$sql_order,$sql_where);
	
		 
	
		$this->currentPage = $pg;
	
		$pgsize = $this->criteria->resultsperpage;
	
		if (!$pgsize) {$pgsize = 15;}
		if ($pg == '' or $pg < 1) {$pg = 1;}
	
		$page = ($pg -1)* $pgsize;
	
		if (!$sql_where) {$sql_where = 1;} else { $this->islimited = true;}
		if (!$sql_order) {$sql_order = 'gs.grid_reference';}
	
	// construct the count query sql
$sql = <<<END
	   SELECT count(*)
		FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
		INNER JOIN user ON(gi.user_id=user.user_id)
			 $sql_from
		WHERE 
			$sql_where
END;
		$this->resultCount = $db->GetOne($sql);
		$this->numberOfPages = ceil($this->resultCount/$pgsize);
	
	// construct the query sql
$sql = <<<END
	   SELECT distinct gi.*,user.realname
			$sql_fields
		FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
		INNER JOIN user ON(gi.user_id=user.user_id)
			 $sql_from
		WHERE 
			$sql_where
		ORDER BY $sql_order
		LIMIT $page,$pgsize
END;
//print $sql;
		//lets find some photos
		$this->results=array();
		$i=0;
		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) 
		{
			$this->results[$i]=new GridImage;
			$this->results[$i]->loadFromRecordset($recordSet);
			$this->results[$i]->compact();
			if ($d = $recordSet->fields['dist_sqd']) {
				$this->results[$i]->dist_string = sprintf("Dist:%.1fkm",sqrt($d));
			}
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close(); 
	}
	
	function getDisplayclass() {
		return $this->criteria->displayclass;
	}
	
	function pagesString() {
		if ($this->currentPage > 1) 
			$r .= "<a href=\"search.php?i={$this->query_id}&amp;page=".($this->currentPage-1)."\">&lt; &lt; prev</a> ";
		$start = max(1,$this->currentPage-5);
		$endr = min($this->numberOfPages+1,$this->currentPage+8);
		
		if ($start > 1)
			$r .= "... ";

		for($index = $start;$index<$endr;$index++) {
			if ($index == $this->currentPage) 

				$r .= "<b>$index</b> "; 
			else
				$r .= "<a href=\"search.php?i={$this->query_id}&amp;page=$index\">$index</a> ";
		}
		
		if ($endr < $this->numberOfPages+1)
			$r .= "... ";

		if ($this->numberOfPages > $this->currentPage) 
			$r .= "<a href=\"search.php?i={$this->query_id}&amp;page=".($this->currentPage+1)."\">next &gt;&gt;</a> ";
		return $r;	
	}
	
	
	/**
	* return true if instance references a valid search
	*/
	function isValid()
	{
		return isset($this->criteria);
	}

	/**
	* assign members from recordset containing required members
	*/
	function loadFromRecordset(&$rs)
	{
		$this->_clear();
		$this->_initFromArray($rs->fields);
		return $this->isValid();
	}

	/**
	 * clear all member vars
	 * @access private
	 */
	function _clear()
	{
		$vars=get_object_vars($this);
		foreach($vars as $name=>$val)
		{
			if ($name!="db")
				unset($this->$name);
		}
	}
	
	/**
	* assign members from array containing required members
	*/
	function _initFromArray(&$arr)
	{
		foreach($arr as $name=>$value)
		{
			if (!is_numeric($name))
				$this->$name=$value;
													
		}
		
	}	


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

	function _trace($msg)
	{
		echo "$msg<br/>";
		flush();
	}	
	function _err($msg)
	{
		echo "<p><b>Error:</b> $msg</p>";
		flush();
	}
	
}



?>