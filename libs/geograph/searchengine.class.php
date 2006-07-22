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

	function buildSimpleQuery($q = '',$distance = 100,$autoredirect='auto',$userlimit = 0)
	{
		global $USER;
		
		if ($distance == 1) {
			$nearstring = 'in';
		} else {
			$nearstring = ($distance)?sprintf("within %dkm of",$distance):'near';
		}
		
		
		$limit1 = '';
		
		$q = trim($q);
		if (preg_match("/^([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9])([A-Z]{0,2})$/",strtoupper($q),$pc)) {
			$searchq = $pc[1].$pc[2]." ".$pc[3];
			$criteria = new SearchCriteria_Postcode();
			$criteria->setByPostcode($searchq);
			if ($criteria->y != 0) {
				$searchclass = 'Postcode';
				$searchdesc = ", $nearstring postcode ".$searchq;
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
				if ($square->imagecount && $autoredirect == 'simple') {
					header("Location:http://{$_SERVER['HTTP_HOST']}/gridref/{$q}");
					print "<a href=\"http://{$_SERVER['HTTP_HOST']}/gridref/{$q}\">View Pictures</a>";
					exit;		
				}
				$searchclass = 'GridRef';
				$searchdesc = ", $nearstring grid reference ".$square->grid_reference;
				$searchx = $square->x;
				$searchy = $square->y;			
			} else {
				$this->errormsg = $square->errormsg;
			}
		} elseif (isset($GLOBALS['text'])) {
			$searchclass = 'Text';
			$searchq = $q;
			$searchdesc = ", containing '{$q}' ";
		} elseif (preg_match('/^\^.*\+$/',$q) || preg_match('/\b(OR|AND|NOT)\b/',$q) || preg_match('/(^|\s+)-([\w^]+)/',$q)) {
			$searchclass = 'Text';
			$searchq = $q;
			$searchdesc = ", matching '".$q."' ";
		} else {
			$criteria = new SearchCriteria_Placename();
			$criteria->setByPlacename($q);
			if ($criteria->is_multiple) {
				//we've found multiple possible placenames
				$searchdesc = ", $nearstring '{$q}'";
				$this->searchdesc = $searchdesc;
				$this->criteria = $criteria;
			} else if (!empty($criteria->placename)) {
				//if one placename then search on that
				$searchclass = 'Placename';
				$searchq = $criteria->placename;
				$searchdesc = ", $nearstring ".$criteria->placename;
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
		
		if ($userlimit) {
			$limit1 = $userlimit;
			$profile=new GeographUser($userlimit);
			$searchdesc .= ", by ".($profile->realname);
		}				

		if (isset($searchclass)) {
			$db=$this->_GetDB();

			$sql = "INSERT INTO queries SET searchclass = '$searchclass',".
			"searchdesc = ".$db->Quote($searchdesc).",".
			"searchuse = ".$db->Quote($this->searchuse).",".
			"searchq = ".$db->Quote($q);
			if ($searchx > 0 && $searchy > 0)
				$sql .= ",x = $searchx,y = $searchy,limit8 = $distance";
			if ($limit1)
				$sql .= ",limit1 = $limit1";
			if (isset($USER) && $USER->registered) {
				$sql .= ",user_id = {$USER->user_id}";
				if (!empty($USER->search_results))
					$sql .= ",resultsperpage = ".$db->Quote($USER->search_results);				
			}	
			$db->Execute($sql);

			$i = $db->Insert_ID();
			if ($autoredirect != false) {
				if (isset($_GET['page']))
					$extra = "&page=".intval($_GET['page']);
				header("Location:http://{$_SERVER['HTTP_HOST']}/{$this->page}?i={$i}".$extra);
				print "<a href=\"http://{$_SERVER['HTTP_HOST']}/{$this->page}?i={$i}$extra\">Your Search Results</a>";
				exit;		
			} else {
				return $i;
			}
		} 
	}

	function buildAdvancedQuery(&$dataarray,$autoredirect='auto')
	{
		global $CONF,$imagestatuses,$sortorders,$USER;
		
		if (empty($dataarray['distance'])) {
			$dataarray['distance'] = $CONF['default_search_distance'];
		}
		if ($dataarray['distance'] == 1) {
			$nearstring = 'in';
		} else {
			$nearstring = sprintf("within %dkm of",$dataarray['distance']);
		}
		
		$searchdesc = '';
		if (!empty($dataarray['placename'])) {
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
		}
		
		if (!empty($dataarray['postcode'])) {
			if (preg_match("/^ *([A-Z]{1,2})([0-9]{1,2}[A-Z]?) *([0-9])([A-Z]{0,2}) *$/",strtoupper($dataarray['postcode']),$pc)) {
				require_once('geograph/searchcriteria.class.php');
				$searchq = $pc[1].$pc[2]." ".$pc[3];
				$criteria = new SearchCriteria_Postcode();
				$criteria->setByPostcode($searchq);
				if ($criteria->y != 0) {
					$searchclass = 'Postcode';
					$searchdesc = ", $nearstring postcode ".$searchq;
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
					$searchdesc = ", $nearstring grid reference ".$square->grid_reference;
					$searchx = $square->x;
					$searchy = $square->y;	
				} else {
					$this->errormsg =  $square->errormsg;
				}
			} else {
				$this->errormsg = "Does not appear to be a valid Grid Reference";
			}
		} else if (!empty($dataarray['county_id'])) {
			require_once('geograph/searchcriteria.class.php');
			$criteria = new SearchCriteria_County();
			$criteria->setByCounty($dataarray['county_id']);
			if (!empty($criteria->county_name)) {
				$searchclass = 'County';
				$searchq = $dataarray['county_id'];
				$searchdesc = ", $nearstring center of ".$criteria->county_name;
				$searchx = $criteria->x;
				$searchy = $criteria->y;	
			} else {
				$this->errormsg =  "Invalid County????";
			}
		} else if (!empty($dataarray['placename'])) {
			$dataarray['placename'] = trim($dataarray['placename']);
			require_once('geograph/searchcriteria.class.php');
			$criteria = new SearchCriteria_Placename();
			$criteria->setByPlacename($dataarray['placename']);
			if (!empty($criteria->placename)) {
				$searchclass = 'Placename';
				$searchq = $criteria->placename;
				$searchdesc = ", $nearstring ".$criteria->placename;
				$searchx = $criteria->x;
				$searchy = $criteria->y;	
			} else if ($criteria->is_multiple) {
				$searchdesc = ", $nearstring '".$dataarray['placename']."'";

			} else {
				$this->errormsg = "Place not found, you might like to try a placename search";
			}
		} else if (!empty($dataarray['textsearch'])) {
			$dataarray['textsearch'] = trim($dataarray['textsearch']);
			$searchclass = 'Text';
			$searchq = $dataarray['textsearch'];
			if (preg_match('/^\^.*\+$/',$dataarray['textsearch']) || preg_match('/\b(OR|AND|NOT)\b/',$dataarray['textsearch']) || preg_match('/(^|\s+)-([\w^]+)/',$dataarray['textsearch'])) {
				$searchdesc = ", matching '".$dataarray['textsearch']."' ";
			} elseif (preg_match('/\+$/',$dataarray['textsearch'])) {
				$searchdesc = ", all about '".preg_replace('/\+$/','',$dataarray['textsearch'])."' ";
			} elseif (preg_match('/^\^/',$dataarray['textsearch'])) {
				$searchdesc = ", matching whole word '".str_replace('^','',$dataarray['textsearch'])."' ";
			} else {
				$searchdesc = ", containing '".$dataarray['textsearch']."' ";	
			}
		} else if (!empty($dataarray['description']) && !empty($dataarray['searchq'])) {
			if (!$dataarray['adminoverride'])
				$USER->mustHavePerm("admin");
			$dataarray['description'] = trim($dataarray['description']);
			$dataarray['searchq'] = trim($dataarray['searchq']);
			$searchclass = 'Special';
			$searchq = $dataarray['searchq'];
			if (preg_match("/;|update |delete |drop |replace |alter |password|email/i",$searchq))
				die("Server Error");
			$searchdesc = ", ".$dataarray['description'];	
			if ($dataarray['x'] > 0 && $dataarray['y'] > 0) {
				$searchx = $dataarray['x'];
				$searchy = $dataarray['y'];
			}
		} else if (!empty($dataarray['all_ind'])) {
			$searchclass = 'All';
			$searchq = '';
		} else {
			$searchclass = 'All';
			$searchq = '';
		} 

		if (isset($searchclass)) {
			$db=NewADOConnection($GLOBALS['DSN']);
			if (empty($db)) die('Database connection failed'); 

			
			
			$sql = "INSERT INTO queries SET searchclass = '$searchclass',".
			"searchuse = ".$db->Quote($this->searchuse).",".
			"searchq = ".$db->Quote($searchq);
			if (isset($dataarray['displayclass']))
				$sql .= ",displayclass = ".$db->Quote($dataarray['displayclass']);
			if (isset($dataarray['resultsperpage'])) {
				$sql .= ",resultsperpage = ".$db->Quote(min(100,$dataarray['resultsperpage']));
			} elseif (isset($USER) && !empty($USER->search_results)) {
				$sql .= ",resultsperpage = ".$db->Quote($USER->search_results);				
			}
			if (isset($searchx) && $searchx > 0 && $searchy > 0)
				$sql .= ",x = $searchx,y = $searchy";
			if (isset($USER) && $USER->registered)
				$sql .= ",user_id = {$USER->user_id}";

			if (!empty($dataarray['user_id'])) {
				$sql .= ",limit1 = ".$db->Quote((!empty($dataarray['user_invert_ind'])?'!':'').$dataarray['user_id']);
				$profile=new GeographUser($dataarray['user_id']);
				$searchdesc .= ",".(!empty($dataarray['user_invert_ind'])?' not':'')." by ".($profile->realname);
			}
			if (!empty($dataarray['moderation_status'])) {
				$sql .= ",limit2 = ".$db->Quote($dataarray['moderation_status']);
				$searchdesc .= ", showing ".$imagestatuses[$dataarray['moderation_status']]." images";
			}
			if (!empty($dataarray['imageclass'])) {
				if ($dataarray['imageclass'] == '-') {
					$sql .= ",limit3 = '-'";
					$searchdesc .= ", unclassifed";
				} else {
					$sql .= ",limit3 = ".$db->Quote($dataarray['imageclass']);
					$searchdesc .= ", classifed as ".$dataarray['imageclass'];
				}
			}
			if (!empty($dataarray['reference_index'])) {
				$sql .= ",limit4 = ".$db->Quote($dataarray['reference_index']);
				$searchdesc .= ", in ".$CONF['references'][$dataarray['reference_index']];
			}
			if (!empty($dataarray['gridsquare'])) {
				$sql .= ",limit5 = ".$db->Quote($dataarray['gridsquare']);
				$searchdesc .= ", in ".$dataarray['gridsquare'];
			}
			
			$this->builddate($dataarray,"submitted_start");
			$this->builddate($dataarray,"submitted_end");
			if (!empty($dataarray['submitted_start']) || !empty($dataarray['submitted_end'])) {
				
				if (!empty($dataarray['submitted_start'])) {
					if (preg_match("/0{4}-([01]?[1-9]+)-/",$dataarray['submitted_start']) > 0) {
						//month only
						$searchdesc .= ", submitted during ".$dataarray['submitted_startString'];
						$dataarray['submitted_end'] = "";
					} else if (!empty($dataarray['submitted_end'])) {
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
			if (!empty($dataarray['taken_start']) || !empty($dataarray['taken_end'])) {
				
				if (!empty($dataarray['taken_start'])) {
					if (preg_match("/0{4}-([01]?[1-9]+)-/",$dataarray['taken_start']) > 0) {
						//month only
						$searchdesc .= ", taken during ".$dataarray['taken_startString'];
						$dataarray['taken_end'] = "";
					} else if (!empty($dataarray['taken_end'])) {
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
			if (!empty($dataarray['distance']) && isset($searchx) && $searchx > 0 && $searchy > 0) {
				$sql .= sprintf(",limit8 = %d",$dataarray['distance']);
			}
			if (!empty($dataarray['topic_id'])) {
				$sql .= ",limit9 = ".$dataarray['topic_id'];
				if ($dataarray['topic_id'] > 1) {
					$topic_name=$db->getOne("SELECT topic_title FROM geobb_topics WHERE topic_id = ".$dataarray['topic_id']);
					$searchdesc .= ", in topic ".$topic_name;
				} else {
					$searchdesc .= ", in any topic";
				}
			}
			if (!empty($dataarray['route_id'])) {
				$sql .= ",limit10 = ".$dataarray['route_id'];
				$topic_name=$db->getOne("SELECT name FROM route WHERE route_id = ".$dataarray['route_id']);
				$searchdesc .= ", on route ".$topic_name;
			}
			
			if (!isset($dataarray['orderby']))
				$dataarray['orderby'] = '';
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
			if ($autoredirect != false) {
				if (isset($_GET['page']))
					$extra = "&page=".intval($_GET['page']);
				header("Location:http://{$_SERVER['HTTP_HOST']}/{$this->page}?i={$i}$extra".(($dataarray['submit'] == 'Count')?'&count=1':''));
				print "<a href=\"http://{$_SERVER['HTTP_HOST']}/{$this->page}?i={$i}$extra".(($dataarray['submit'] == 'Count')?'&amp;count=1':'')."\">Your Search Results</a>";
				exit;
			} else {
				return $i;
			}
		} else if (isset($criteria) && isset($criteria->is_multiple)) {
			if ($dataarray['user_id']) {
				$profile=new GeographUser($dataarray['user_id']);
				$searchdesc .= ",".($dataarray['user_invert_ind']?' not':'')." by ".($profile->realname);
			}
			if ($dataarray['moderation_status']) {
				$searchdesc .= ", showing ".$imagestatuses[$dataarray['moderation_status']]." images";
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
		if (isset($dataarray[$which.'Year'])) {
			$dataarray[$which] = sprintf("%04d-%02d-%02d",$dataarray[$which.'Year'],$dataarray[$which.'Month'],$dataarray[$which.'Day']);
			if ($dataarray[$which] == '0000-00-00') {
				$dataarray[$which] = ''; 
			}
		}
		if (!empty($dataarray[$which])) {
			$image = new GridImage();
			$image->imagetaken = $dataarray[$which];					
			$dataarray[$which.'String'] = $image->getFormattedTakenDate();
		}
	}	
	
	function ExecuteReturnRecordset($pg,$extra_fields = '') 
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

		if ($pg > 1 || $this->countOnly) {
		
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
		if ($this->countOnly || ($pg > 1 && !$this->resultCount))
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

		if ($pg == 1) {
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

		if ($pg > 1 || $this->countOnly) {
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
		if ($this->countOnly || ($pg > 1 && !$this->resultCount))
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
		
		if ($pg == 1) {
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

				if (isset($recordSet->fields['dist_sqd'])) {
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