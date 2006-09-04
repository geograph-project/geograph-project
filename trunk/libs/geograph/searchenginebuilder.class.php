<?php
/**
 * $Project: GeoGraph $
 * $Id: searchengine.class.php 2338 2006-07-22 12:21:30Z barryhunter $
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
* Provides the SearchEngineBuilder class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision: 2338 $
*/


/**
* SearchEngine
*
* 
* @package Geograph
*/
class SearchEngineBuilder extends SearchEngine
{


	/**
	* create a simple search object
	*/
	
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
		} elseif (preg_match("/\b([a-zA-Z]{1,2}) ?(\d{2,5})[ \.]?(\d{2,5})\b/",$q,$gr)) {
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
		} elseif (preg_match("/^(-?\d+\.?\d*),(-?\d+\.?\d*)$/",$q,$ll)) {
			require_once('geograph/conversions.class.php');
			require_once('geograph/gridsquare.class.php');
			$square=new GridSquare;
			$conv = new Conversions;
			list($x,$y,$reference_index) = $conv->wgs84_to_internal($ll[1],$ll[2]);
			$grid_ok=$square->loadFromPosition($x, $y, true);
			if ($grid_ok) {
				$searchclass = 'GridRef';
				list($latdm,$longdm) = $conv->wgs84_to_friendly($ll[1],$ll[2]);
				$searchdesc = ", $nearstring $latdm, $longdm";
				$searchq = $q = $square->grid_reference;
				$searchx = $x;
				$searchy = $y;			
			} else {
				$this->errormsg = "unable to parse lat/long";
			}
		} elseif (preg_match('/(^\^|\+$)/',$q) || preg_match('/\b(OR|AND|NOT)\b/',$q) || preg_match('/(^|\s+)-([\w^]+)/',$q)) {
			$searchclass = 'Text';
			$searchq = $q;
			$searchdesc = ", matching '".$q."' ";
		} elseif (isset($GLOBALS['text'])) {
			$searchclass = 'Text';
			$searchq = $q;
			$searchdesc = ", containing '{$q}' ";
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


	/**
	* create a more complex search object
	*/
	
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
					if (preg_match("/0{4}-([01]?[1-9]+|10)-/",$dataarray['submitted_start']) > 0) {
						//month only
						$searchdesc .= ", submitted during ".$dataarray['submitted_startString'];
						$dataarray['submitted_end'] = "";
					} elseif (preg_match("/0{4}-0{2}-([0-3]?[1-9]+|10|20|30)/",$dataarray['submitted_start']) > 0) {
						//day only
						$searchdesc .= ", submitted in the last ".$dataarray['submitted_startDay']." days";
						$dataarray['submitted_end'] = "";
					} elseif (!empty($dataarray['submitted_end'])) {
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
					if (preg_match("/0{4}-([01]?[1-9]+|10)-/",$dataarray['taken_start']) > 0) {
						//month only
						$searchdesc .= ", taken during ".$dataarray['taken_startString'];
						$dataarray['taken_end'] = "";
					} elseif (preg_match("/0{4}-0{2}-([0-3]?[1-9]+|10|20|30)/",$dataarray['taken_start']) > 0) {
						//day only
						$searchdesc .= ", taken in the last ".$dataarray['taken_startDay']." days";
						$dataarray['submitted_end'] = "";
					} elseif (!empty($dataarray['taken_end'])) {
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

	
}



?>