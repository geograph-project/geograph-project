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
	
	function SearchEngine($query_id)
	{
		if (is_numeric($query_id)) {
	
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
				case "All":
					$this->criteria = new SearchCriteria_All($query['q']);
					break;
				case "Text":
					$this->criteria = new SearchCriteria_Text($query['q']);
					break;
			}

			$this->criteria->_initFromArray($query);
		} 

  
	} 

	function buildSimpleQuery($q = '')
	{
		global $USER;
		$q = trim($q);
		if (preg_match("/^([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9])([A-Z]{0,2})$/",strtoupper($q),$pc)) {
			$searchq = $pc[1].$pc[2]." ".$pc[3];
			$criteria = new SearchCriteria_Postcode();
			$criteria->setByPostcode($searchq);
			if ($criteria->y != 0) {
				$searchclass = 'Postcode';
				$searchdesc = ", near postcode ".$searchq;
				$searchx = $criteria->x;
				$searchy = $criteria->y;	
			} else {
				$this->errormsg = "Invalid Postcode";
			}
		} else if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/",$q,$gr)) {
			require_once('geograph/gridsquare.class.php');
			$square=new GridSquare;
			$grid_ok=$square->setByFullGridRef($q);
			if ($grid_ok) {
				$searchclass = 'GridRef';
				$searchdesc = ", near grid reference ".$square->grid_reference;
				$searchx = $square->x;
				$searchy = $square->y;			
			} else {
				$this->errormsg = $square->errormsg;
			}
		} else {
			$criteria = new SearchCriteria_Placename();
			$criteria->setByPlacename($q);
			if ($criteria->is_multiple) {
				//we've found multiple possible placenames
				$searchdesc = ", near '".$q."'";
				$this->searchdesc = $searchdesc;
				$this->criteria = $criteria;
			} else if (!empty($criteria->placename)) {
				//if one placename then search on that
				$searchclass = 'Placename';
				$searchq = $criteria->placename;
				$searchdesc = ", near ".$criteria->placename;
				$searchx = $criteria->x;
				$searchy = $criteria->y;	
			} else {
				//check if this is a user 
				$criteria = new SearchCriteria_All();
				$criteria->setByUsername($q);
				if (!empty($criteria->realname)) {
					$searchclass = 'All';
					$searchq = '';
					$limit1 = $criteria->user_id;
					$searchdesc = ", by '{$criteria->realname}' ";
				} else {
					//asuume a text search
					$searchclass = 'Text';
					$searchq = $q;
					$searchdesc = ", containing '{$q}' ";
				}
			}
		}

		if ($searchclass) {
			$db=$this->_GetDB();

			$sql = "INSERT INTO queries SET searchclass = '$searchclass',".
			"searchdesc = ".$db->Quote($searchdesc).",".
			"searchuse = ".$db->Quote($this->searchuse).",".
			"searchq = ".$db->Quote($q);
			if ($searchx > 0 && $searchy > 0)
				$sql .= ",x = $searchx,y = $searchy";
			if ($limit1)
				$sql .= ",limit1 = $limit1";
			if ($USER->registered)
				$sql .= ",user_id = {$USER->user_id}";

			$db->Execute($sql);

			$i = $db->Insert_ID();
			header("Location:http://{$_SERVER['HTTP_HOST']}/{$this->page}?i={$i}");
			print "<a href=\"http://{$_SERVER['HTTP_HOST']}/{$this->page}?i={$i}\">Your Search Results</a>";
			exit;		
		} 
	}

	function buildAdvancedQuery(&$dataarray)
	{
		global $CONF,$imagestatuses,$sortorders,$USER;
		
		//check if we actully want to perform a textsearch (it comes through in the placename beucase of the way the multiple mathc page works)
		if (strpos($dataarray['placename'],'text:') === 0) {
			$dataarray['textsearch'] = preg_replace("/^text\:/",'',$dataarray['placename']);
			unset($dataarray['placename']);
		}
		//check if we actully want to perform a user_search
		if (strpos($dataarray['placename'],'user:') === 0) {
			$dataarray['user_id'] = preg_replace("/^user\:/",'',$dataarray['placename']);
			unset($dataarray['placename']);
		}
		
		if (!empty($dataarray['postcode'])) {
			if (preg_match("/^ *([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9])([A-Z]{0,2}) *$/",strtoupper($dataarray['postcode']),$pc)) {
				require_once('geograph/searchcriteria.class.php');
				$searchq = $pc[1].$pc[2]." ".$pc[3];
				$criteria = new SearchCriteria_Postcode();
				$criteria->setByPostcode($searchq);
				if ($criteria->y != 0) {
					$searchclass = 'Postcode';
					$searchdesc = ", near postcode ".$searchq;
					$searchx = $criteria->x;
					$searchy = $criteria->y;	
				} else {
					$this->errormsg = "Postcode Not Found...";
				}
			} else {
				$this->errormsg = "Does not appear to be a valid Postcode";
			}
		} else if (!empty($dataarray['gridref'])) {
			if (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/",$dataarray['gridref'],$gr)) {
				require_once('geograph/gridsquare.class.php');
				$square=new GridSquare;
				$grid_ok=$square->setByFullGridRef($dataarray['gridref']);
				if ($grid_ok) {
					$searchclass = 'GridRef';
					$searchq = $dataarray['gridref'];
					$searchdesc = ", near grid reference ".$square->grid_reference;
					$searchx = $square->x;
					$searchy = $square->y;	
				} else {
					$this->errormsg =  $square->errormsg;
				}
			} else {
				$this->errormsg = "Does not appear to be a valid Grid Reference";
			}
		} else if ($dataarray['county_id']) {
			require_once('geograph/searchcriteria.class.php');
			$criteria = new SearchCriteria_County();
			$criteria->setByCounty($dataarray['county_id']);
			if (!empty($criteria->county_name)) {
				$searchclass = 'County';
				$searchq = $dataarray['county_id'];
				$searchdesc = ", near center of ".$criteria->county_name;
				$searchx = $criteria->x;
				$searchy = $criteria->y;	
			} else {
				$this->errormsg =  "Invalid County????";
			}
		} else if ($dataarray['placename']) {
			$dataarray['placename'] = trim($dataarray['placename']);
			require_once('geograph/searchcriteria.class.php');
			$criteria = new SearchCriteria_Placename();
			$criteria->setByPlacename($dataarray['placename']);
			if (!empty($criteria->placename)) {
				$searchclass = 'Placename';
				$searchq = $criteria->placename;
				$searchdesc = ", near ".$criteria->placename;
				$searchx = $criteria->x;
				$searchy = $criteria->y;	
			} else if ($criteria->is_multiple) {
				$searchdesc = ", near '".$dataarray['placename']."'";

			} else {
				$this->errormsg = "Place not found, you might like to try a placename search";
			}
		} else if (!empty($dataarray['textsearch'])) {
			$dataarray['textsearch'] = trim($dataarray['textsearch']);
			$searchclass = 'Text';
			$searchq = $dataarray['textsearch'];
			
			$searchdesc = ", containing '".str_replace('^','',$dataarray['textsearch'])."' ";	
		} else if (!empty($dataarray['all_ind'])) {
			$searchclass = 'All';
		} else {
			$searchclass = 'All';
		} 

		if ($searchclass) {
			$db=NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed'); 

			
			
			$sql = "INSERT INTO queries SET searchclass = '$searchclass',".
			"searchuse = ".$db->Quote($this->searchuse).",".
			"searchq = ".$db->Quote($searchq);
			if ($dataarray['displayclass'])
				$sql .= ",displayclass = ".$db->Quote($dataarray['displayclass']);
			if ($dataarray['resultsperpage'])
				$sql .= ",resultsperpage = ".$db->Quote($dataarray['resultsperpage']);
			if ($searchx > 0 && $searchy > 0)
				$sql .= ",x = $searchx,y = $searchy";
			if ($USER->registered)
				$sql .= ",user_id = {$USER->user_id}";

			if ($dataarray['user_id']) {
				$sql .= ",limit1 = ".$db->Quote(($dataarray['user_invert_ind']?'!':'').$dataarray['user_id']);
				$profile=new GeographUser($dataarray['user_id']);
				$searchdesc .= ",".($dataarray['user_invert_ind']?' not':'')." by ".($profile->realname);
			}
			if ($dataarray['moduration_status']) {
				$sql .= ",limit2 = ".$db->Quote($dataarray['moduration_status']);
				$searchdesc .= ", showing ".$imagestatuses[$dataarray['moduration_status']]." images";
			}
			if ($dataarray['imageclass']) {
				if ($dataarray['imageclass'] == '-') {
					$sql .= ",limit3 = '-'";
					$searchdesc .= ", unclassifed";
				} else {
					$sql .= ",limit3 = ".$db->Quote($dataarray['imageclass']);
					$searchdesc .= ", classifed as ".$dataarray['imageclass'];
				}
			}
			if ($dataarray['reference_index']) {
				$sql .= ",limit4 = ".$db->Quote($dataarray['reference_index']);
				$searchdesc .= ", in ".$CONF['references'][$dataarray['reference_index']];
			}
			if ($dataarray['gridsquare']) {
				$sql .= ",limit5 = ".$db->Quote($dataarray['gridsquare']);
				$searchdesc .= ", in ".$dataarray['gridsquare'];
			}
			
			$this->builddate($dataarray,"submitted_start");
			$this->builddate($dataarray,"submitted_end");
			if ($dataarray['submitted_start'] || $dataarray['submitted_end']) {
				
				if ($dataarray['submitted_start']) {
					if (preg_match("/0{4}-(1?[1-9]+)-/",$dataarray['submitted_start']) > 0) {
						//month only
						$searchdesc .= ", submitted during ".$dataarray['submitted_startString'];
						$dataarray['submitted_end'] = "";
					} else if ($dataarray['submitted_end']) {
						if ($dataarray['submitted_end'] == $dataarray['submitted_start']) {
							//both the same
							$searchdesc .= ", submitted ".(is_numeric($dataarray['submitted_startString'])?'in ':'').$dataarray['submitted_startString'];
						} else {
							//between
								
								//if the start date is later than the end then lets swap them!
								$startdate = vsprintf("%04d%02%02",explode('-',$dataarray['submitted_start']));
								$enddate = vsprintf("%04d%02%02",explode('-',$dataarray['submitted_end']));
								if ($startdate > $enddate) {
									$temp = $dataarray['submitted_startString'];
									$dataarray['submitted_startString'] = $dataarray['submitted_endString'];
									$dataarray['submitted_endString'] = $temp;
									$temp = $dataarray['submitted_start'];
									$dataarray['submitted_start'] = $dataarray['submitted_end'];
									$dataarray['submitted_end'] = $temp;
								}
							
							$searchdesc .= ", submitted between ".$dataarray['submitted_startString']." and ".$dataarray['submitted_endString']." ";
						}
					} else {
						//from
						$searchdesc .= ", submitted after ".$dataarray['submitted_startString'];
					}
				} else {
					//to
					$searchdesc .= ", submitted before ".$dataarray['submitted_endString'];
				}
			
				$sql .= ",limit6 = '{$dataarray['submitted_start']}^{$dataarray['submitted_end']}'";
			}
			
			$this->builddate($dataarray,"taken_start");
			$this->builddate($dataarray,"taken_end");
			if ($dataarray['taken_start'] || $dataarray['taken_end']) {
				
				if ($dataarray['taken_start']) {
					if (preg_match("/0{4}-(1?[1-9]+)-/",$dataarray['taken_start']) > 0) {
						//month only
						$searchdesc .= ", taken during ".$dataarray['taken_startString'];
						$dataarray['taken_end'] = "";
					} else if ($dataarray['taken_end']) {
						if ($dataarray['taken_end'] == $dataarray['taken_start']) {
							//both the same
							$searchdesc .= ", taken ".(is_numeric($dataarray['taken_startString'])?'in ':'').$dataarray['taken_startString'];
						} else {
							//between
							
								//if the start date is later than the end then lets swap them!
								$startdate = vsprintf("%04d%02%02",explode('-',$dataarray['taken_start']));
								$enddate = vsprintf("%04d%02%02",explode('-',$dataarray['taken_end']));
								if ($startdate > $enddate) {
									$temp = $dataarray['taken_startString'];
									$dataarray['taken_startString'] = $dataarray['taken_endString'];
									$dataarray['taken_endString'] = $temp;
									$temp = $dataarray['taken_start'];
									$dataarray['taken_start'] = $dataarray['taken_end'];
									$dataarray['taken_end'] = $temp;
								}
							
							$searchdesc .= ", taken between ".$dataarray['taken_startString']." and ".$dataarray['taken_endString']." ";
						}
					} else {
						//from
						$searchdesc .= ", taken after ".$dataarray['taken_startString'];
					}
				} else {
					//to
					$searchdesc .= ", taken before ".$dataarray['taken_endString'];
				}
			
				$sql .= ",limit7 = '{$dataarray['taken_start']}^{$dataarray['taken_end']}'";
			}
			switch ($dataarray['orderby']) {
				case "":
					if ($searchclass == 'All') {
						$sql .= ",orderby = 'random'";
						$searchdesc .= ", in random order";
					}
					break;
				case "random":
					$sql .= ",orderby = ".$db->Quote($dataarray['orderby']);
					$searchdesc .= ", in Random order";
					break;
				case "dist_sqd":
					break;
				default:
					$sql .= ",orderby = ".$db->Quote($dataarray['orderby'].($dataarray['reverse_order_ind']?' desc':''));
					if ($dataarray['reverse_order_ind']) {
						if (strpos($sortorders[$dataarray['orderby']],'-') > 1) {
							$searchdesc .= ", in ".(implode('-&gt;',array_reverse(explode('-&gt;',$sortorders[$dataarray['orderby']]))))." order";
						} else {
							$searchdesc .= ", in reverse ".($sortorders[$dataarray['orderby']])." order";
						}
					} else {
						$searchdesc .= ", in ".($sortorders[$dataarray['orderby']])." order";

					}
			}
			

			$sql .= ",searchdesc = ".$db->Quote($searchdesc);

			$db->Execute($sql);

			$i = $db->Insert_ID();
			header("Location:http://{$_SERVER['HTTP_HOST']}/{$this->page}?i={$i}");
			print "<a href=\"http://{$_SERVER['HTTP_HOST']}/{$this->page}?i={$i}\">Your Search Results</a>";
			exit;		
		} else if ($criteria->is_multiple) {
			if ($dataarray['user_id']) {
				$profile=new GeographUser($dataarray['user_id']);
				$searchdesc .= ",".($dataarray['user_invert_ind']?' not':'')." by ".($profile->realname);
			}
			if ($dataarray['moduration_status']) {
				$searchdesc .= ", showing ".$imagestatuses[$dataarray['moduration_status']]." images";
			}
			if ($dataarray['imageclass']) {
				$searchdesc .= ", classifed as ".$dataarray['imageclass'];
			}
			if ($dataarray['reference_index']) {
				$searchdesc .= ", in ".$CONF['references'][$dataarray['reference_index']];
			}
			if ($dataarray['gridsquare']) {
				$searchdesc .= ", in ".$dataarray['gridsquare'];
			}
			
			if (!empty($dataarray['orderby'])) {
				switch ($dataarray['orderby']) {
					case "":
						break;
					case "random":
						$searchdesc .= ", in Random order";
						break;
					case "dist_sqd":
						break;
					default:
						$searchdesc .= ", in ".($dataarray['reverse_order_ind']?'reverse ':'').($sortorders[$dataarray['orderby']])." order";
				}
			}
	
			$this->searchdesc = $searchdesc;
			$this->criteria = $criteria;
	
		}
	}
	
	function builddate(&$dataarray,$which) {
		$dataarray[$which] = sprintf("%04d-%d-%02d",$dataarray[$which.'Year'],$dataarray[$which.'Month'],$dataarray[$which.'Day']);
		//single digit month need to get round bug in smarty, luckily sql should cope!
		if ($dataarray[$which] == '0000-0-00') {
			$dataarray[$which] = ''; 
		} else {
			$image = new GridImage();
			$image->imagetaken = $dataarray[$which];					
			$dataarray[$which.'String'] = $image->getFormattedTakenDate();
		}
	}	
	
	function Execute($pg) 
	{
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
	
		//need to ensure rejected images arent shown
		if (empty($sql_where)) {
			$sql_where = "moderation_status != 'rejected'";
		} else {
			$this->islimited = true;
			if (strpos($sql_where,'moderation_status') === FALSE) 
				$sql_where .= " and moderation_status != 'rejected'";
		}
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
	   SELECT distinct gi.*,x,y,user.realname
			$sql_fields
		FROM gridimage AS gi INNER JOIN gridsquare AS gs USING(gridsquare_id)
		INNER JOIN user ON(gi.user_id=user.user_id)
			 $sql_from
		WHERE 
			$sql_where
		ORDER BY $sql_order
		LIMIT $page,$pgsize
END;
#print "<BR><BR>$sql";
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
				$angle = rad2deg(atan2( $recordSet->fields['x']-$this->criteria->x, $recordSet->fields['y']-$this->criteria->y ));
				$this->results[$i]->dist_string = sprintf("Dist:%.1fkm %s",sqrt($d),$this->heading_string($angle));
			}
			
			//if we searching on imageclass then theres no point displaying it...
			if ($this->criteria->limit3) {
				unset($this->results[$i]->imageclass);
			}
			
			//if we searching on taken date then display it...
			if ($this->criteria->limit7) {
				$this->results[$i]->imagetakenString = $this->results[$i]->getFormattedTakenDate();
			}
						
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close(); 
	}
	
	function heading_string($deg) {
		
		$dirs = array('north','east','south','west'); 
		$rounded = round($deg / 22.5) % 16; 
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
	
	function pagesString() {
		if ($this->currentPage > 1) 
			$r .= "<a href=\"{$this->page}?i={$this->query_id}&amp;page=".($this->currentPage-1)."\">&lt; &lt; prev</a> ";
		$start = max(1,$this->currentPage-5);
		$endr = min($this->numberOfPages+1,$this->currentPage+8);
		
		if ($start > 1)
			$r .= "<a href=\"{$this->page}?i={$this->query_id}&amp;page=1\">1</a> ... ";

		for($index = $start;$index<$endr;$index++) {
			if ($index == $this->currentPage) 
				$r .= "<b>$index</b> "; 
			else
				$r .= "<a href=\"{$this->page}?i={$this->query_id}&amp;page=$index\">$index</a> ";
		}
		if ($endr < $this->numberOfPages+1) {
			$index = $this->numberOfPages;
			$r .= "... <a href=\"{$this->page}?i={$this->query_id}&amp;page=$index\">$index</a> ";
		}
			
		if ($this->numberOfPages > $this->currentPage) 
			$r .= "<a href=\"{$this->page}?i={$this->query_id}&amp;page=".($this->currentPage+1)."\">next &gt;&gt;</a> ";
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