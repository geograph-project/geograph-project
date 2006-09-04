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
	* criteria object
	*/
	var $criteria;
	
	var $results;
	
	var $resultCount = 0;
	var $numberOfPages;
	var $currentPage;

	var $page = "search.php";
	var $searchuse = "search";

	/**
	* true if a where cluase is in effect
	*/	
	var $islimited = false;
	
	var $errormsg;
	
	//don't use the cached version of exercute
	var $noCache = false;
	
	//only run the count section of exercute
	var $countOnly = false;
	
	function SearchEngine($query_id = '')
	{
		if (is_numeric($query_id)) {
	
			$this->query_id = $query_id;

			$db=$this->_getDB();

			$query = $db->GetRow("SELECT *,crt_timestamp+0 as crt_timestamp_ts FROM queries WHERE id = $query_id LIMIT 1");

			$classname = "SearchCriteria_".$query['searchclass'];
			$this->criteria = new $classname($query['q']);
			
			if ($query['searchclass'] == "Special")	{
					$query['searchq'] = stripslashes($query['searchq']);
			}

			$this->criteria->_initFromArray($query);
		} 

  
	} 
	
	function ExecuteReturnRecordset($pg,$extra_fields = '') 
	{
		global $CONF;
		$db=$this->_getDB();
		
		
		$sql_fields = "";
		$sql_order = "";
		$sql_where = "";
		$sql_from = "";
		
		$this->criteria->getSQLParts($sql_fields,$sql_order,$sql_where,$sql_from);
	
		$this->currentPage = $pg;
	
		$pgsize = $this->criteria->resultsperpage;
	
		if (!$pgsize) {$pgsize = 15;}
		if ($pg == '' or $pg < 1) {$pg = 1;}
	
		$page = ($pg -1)* $pgsize;
	
		//need to ensure rejected/pending images arent shown
		if (empty($sql_where)) {
			$sql_where = " moderation_status in ('accepted','geograph')";
		} else {
			$this->islimited = true;
			if (strpos($sql_where,'moderation_status') === FALSE) 
				$sql_where .= " and moderation_status in ('accepted','geograph')";
		}
		if (!$sql_order) {$sql_order = 'gs.grid_reference';}
		
		if (preg_match("/^(left |inner |)join ([\w\,\(\) \.\'!=]+) where/i",$sql_where,$matches)) {
			$sql_where = preg_replace("/^(left |inner |)join ([\w\,\(\) \.!=\']+) where/i",'',$sql_where);
			$sql_from .= " {$matches[1]} join {$matches[2]}";
		}

		if ($pg > 1 || $CONF['search_count_first_page'] || $this->countOnly) {
		
			$count_from = (strpos($sql_where,'gs.') !== FALSE)?"INNER JOIN gridsquare AS gs USING(gridsquare_id)":'';
			$count_from .= (strpos($sql_where,'user.') !== FALSE)?" INNER JOIN user ON(gi.user_id=user.user_id)":'';
			##$count_from = "INNER JOIN gridsquare AS gs USING(gridsquare_id)";
			
			// construct the count query sql
			if (preg_match("/group by ([\w\,\(\) ]+)/i",$sql_where,$matches)) {
				$sql_where2 = preg_replace("/group by ([\w\,\(\) ]+)/i",'',$sql_where);
$sql = <<<END
	   SELECT count(DISTINCT {$matches[1]})
		FROM gridimage AS gi $count_from
			 $sql_from
		WHERE 
			$sql_where2
END;
			} else {
$sql = <<<END
	   SELECT count(*)
		FROM gridimage AS gi $count_from
			 $sql_from
		WHERE 
			$sql_where
END;
			}
			if (!empty($_GET['debug']))
				print "<BR><BR>$sql";
			$this->resultCount = $db->CacheGetOne(3600,$sql);
			$db->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");
			$this->numberOfPages = ceil($this->resultCount/$pgsize);
		} 
		if ($this->countOnly || ( ($pg > 1 || $CONF['search_count_first_page']) && !$this->resultCount))
			return 0;
		
	// construct the query sql
$sql = <<<END
	   SELECT gi.*,x,y,gs.grid_reference,user.realname
			$sql_fields $extra_fields
		FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
		INNER JOIN user ON(gi.user_id=user.user_id)
			 $sql_from
		WHERE 
			$sql_where
		ORDER BY $sql_order
		LIMIT $page,$pgsize
END;
		if (!empty($_GET['debug']))
			print "<BR><BR>$sql";
		
		list($usec, $sec) = explode(' ',microtime());
		$querytime_before = ((float)$usec + (float)$sec);
				
		$recordSet = &$db->Execute($sql);
		
		list($usec, $sec) = explode(' ',microtime());
		$querytime_after = ((float)$usec + (float)$sec);
						
		$this->querytime =  $querytime_after - $querytime_before;

		if ($pg == 1 && !$CONF['search_count_first_page']) {
			$count = $db->getOne("select `count` from queries_count where id = {$this->query_id}");
			if ($count) {
				$this->resultCount = $count;
				$this->numberOfPages = ceil($this->resultCount/$pgsize);
			} else {
				$this->resultCount = $recordSet->RecordCount();
				if ($this->resultCount == $pgsize) {
					$this->numberOfPages = 2;
					$this->pageOneOnly = 1;
				} else {
					$this->numberOfPages = ceil($this->resultCount/$pgsize);
					$db->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");
				}
			}
		}

		return $recordSet;
	}

	function ExecuteCachedReturnRecordset($pg) 
	{
		global $CONF;
		$db=$this->_getDB();
		
		
		$sql_fields = "";
		$sql_order = "";
		$sql_where = "";
		$sql_from = "";
		
		$this->criteria->getSQLParts($sql_fields,$sql_order,$sql_where,$sql_from);
	
		$this->currentPage = $pg;
	
		$pgsize = $this->criteria->resultsperpage;
	
		if (!$pgsize) {$pgsize = 15;}
		if ($pg == '' or $pg < 1) {$pg = 1;}
	
		$page = ($pg -1)* $pgsize;
	
		if (!empty($sql_where)) {
			$sql_where = "WHERE $sql_where";
			$this->islimited = true;
		}
		if (!$sql_order) {$sql_order = 'grid_reference';}
	
		if (strpos($sql_where,'gs') !== FALSE) {
			$sql_where = str_replace('gs.','gi.',$sql_where);
		}
		$sql_fields = str_replace('gs.','gi.',$sql_fields);
		
		if (preg_match("/^(left |inner |)join ([\w\,\(\) \.\'!=]+) where/i",$sql_where,$matches)) {
			$sql_where = preg_replace("/^(left |inner |)join ([\w\,\(\) \.!=\']+) where/i",'',$sql_where);
			$sql_from .= " {$matches[1]} join {$matches[2]}";
		}

		if ($pg > 1 || $CONF['search_count_first_page'] || $this->countOnly) {
			// construct the count sql
			if (preg_match("/group by ([\w\,\(\) ]+)/i",$sql_where,$matches)) {
				$sql_where2 = preg_replace("/group by ([\w\,\(\) ]+)/i",'',$sql_where);
$sql = <<<END
	   SELECT count(DISTINCT {$matches[1]})
		FROM gridimage_search as gi
			 $sql_from
		$sql_where2
END;
			} else {
$sql = <<<END
	   SELECT count(*)
		FROM gridimage_search as gi
			 $sql_from
		$sql_where
END;
			}
			if (!empty($_GET['debug']))
				print "<BR><BR>$sql";


			$this->resultCount = $db->CacheGetOne(3600,$sql);
			$db->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");

			$this->numberOfPages = ceil($this->resultCount/$pgsize);
		}
		if ($this->countOnly || ( ($pg > 1 || $CONF['search_count_first_page']) && !$this->resultCount))
			return 0;
	// construct the query sql
$sql = <<<END
		SELECT gi.*
			$sql_fields
		FROM gridimage_search as gi
			 $sql_from
		$sql_where
		ORDER BY $sql_order
		LIMIT $page,$pgsize
END;
		if (!empty($_GET['debug']))
			print "<BR><BR>$sql";
		
		list($usec, $sec) = explode(' ',microtime());
		$querytime_before = ((float)$usec + (float)$sec);
				
		$recordSet = &$db->Execute($sql);
				
		list($usec, $sec) = explode(' ',microtime());
		$querytime_after = ((float)$usec + (float)$sec);
		
		$this->querytime =  $querytime_after - $querytime_before;
		
		if ($pg == 1 && !$CONF['search_count_first_page']) {
			$count = $db->getOne("select `count` from queries_count where id = {$this->query_id}");
			if ($count) {
				$this->resultCount = $count;
				$this->numberOfPages = ceil($this->resultCount/$pgsize);
			} else {
				$this->resultCount = $recordSet->RecordCount();
				if ($this->resultCount == $pgsize) {
					$this->numberOfPages = 2;
					$this->pageOneOnly = 1;
				} else {
					$this->numberOfPages = ceil($this->resultCount/$pgsize);
					$db->Execute("replace into queries_count set id = {$this->query_id},`count` = {$this->resultCount}");
				}
			}
		}
		
		return $recordSet;
	}
	
	function ReturnRecordset($pg,$nocache = false) {
		if ($nocache || $this->noCache || ($this->criteria->searchclass == 'Special' && preg_match('/(gs|gi|user)\./',$this->criteria->searchq))) {
			//a Special Search needs full access to GridImage/GridSquare/User
			$recordSet =& $this->ExecuteReturnRecordset($pg);
		} else {
			$recordSet =& $this->ExecuteCachedReturnRecordset($pg); 
		}
		return $recordSet;
	}
		
	function Execute($pg) 
	{
		if ($this->noCache || ($this->criteria->searchclass == 'Special' && preg_match('/(gs|gi|user)\./',$this->criteria->searchq))) {
			//a Special Search needs full access to GridImage/GridSquare/User
			$recordSet =& $this->ExecuteReturnRecordset($pg);
		} else {
			$recordSet =& $this->ExecuteCachedReturnRecordset($pg); 
		}
		//we dont actully want to process anything
		if ($this->countOnly)
			return 0;
			
		if ($recordSet)	{
			$dist_format = ($this->criteria->searchclass == 'Postcode')?"Dist:%dkm %s":"Dist:%.1fkm %s";		

			$this->results=array();
			$i=0;

			$showtaken = ($this->criteria->limit7 || preg_match('/^imagetaken/',$this->criteria->orderby));

			while (!$recordSet->EOF) 
			{
				$this->results[$i]=new GridImage;
				$this->results[$i]->fastInit($recordSet->fields);

				if (!empty($recordSet->fields['dist_sqd'])) {
					$angle = rad2deg(atan2( $recordSet->fields['x']-$this->criteria->x, $recordSet->fields['y']-$this->criteria->y ));
					$this->results[$i]->dist_string = sprintf($dist_format,sqrt($recordSet->fields['dist_sqd']),$this->heading_string($angle));
				}
				if (empty($this->results[$i]->title))
					$this->results[$i]->title="Untitled";

				//if we searching on imageclass then theres no point displaying it...
				if ($this->criteria->limit3) 
					unset($this->results[$i]->imageclass);

				//if we searching on taken date then display it...
				if ($showtaken) 
					$this->results[$i]->imagetakenString = $this->results[$i]->getFormattedTakenDate();

				$recordSet->MoveNext();
				$i++;
			}
			$recordSet->Close(); 
			$this->numberofimages = $i;
		} else 
			return 0;
			
		return $this->querytime;
	}
	
	function heading_string($deg) {
		$dirs = array('north','east','south','west'); 
		$rounded = round($deg / 22.5) % 16; 
		if ($rounded < 0)
			$rounded += 16;
		if (($rounded % 4) == 0) { 
			$s = $dirs[$rounded/4]; 
		} else { 
			$s = $dirs[2 * intval(((intval($rounded / 4) + 1) % 4) / 2)]; 
			$s .= $dirs[1 + 2 * intval($rounded / 8)]; 
			if ($rounded % 2 == 1) { 
				$s = $dirs[round($rounded/4) % 4] . '-' . $s;
			} 
		} 
		return $s; 
	} 
	
	function getDisplayclass() {
		return $this->criteria->displayclass;
	}
	
	function pagesString($postfix = '') {
		static $r;
		if (!empty($r))
			return($r);
		if ($this->currentPage > 1) 
			$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=".($this->currentPage-1)."$postfix\">&lt; &lt; prev</a> ";
		$start = max(1,$this->currentPage-5);
		$endr = min($this->numberOfPages+1,$this->currentPage+8);
		
		if ($start > 1)
			$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=1$postfix\">1</a> ... ";

		for($index = $start;$index<$endr;$index++) {
			if ($index == $this->currentPage && !$this->countOnly) 
				$r .= "<b>$index</b> "; 
			else
				$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=$index$postfix\">$index</a> ";
		}
		if ($endr < $this->numberOfPages+1) {
			$index = $this->numberOfPages;
			$r .= "... <a href=\"/{$this->page}?i={$this->query_id}&amp;page=$index$postfix\">$index</a> ";
		}
		
		if ($this->pageOneOnly) 
			$r .= "... ";
			
		if ( ($this->numberOfPages > $this->currentPage || $this->pageOneOnly ) && !$this->countOnly) 
			$r .= "<a href=\"/{$this->page}?i={$this->query_id}&amp;page=".($this->currentPage+1)."$postfix\">next &gt;&gt;</a> ";
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
	
	
	/**
	* store error message
	*/
	function _error($msg)
	{
		$this->errormsg=$msg;
	}
	
}



?>