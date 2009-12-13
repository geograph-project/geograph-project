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
* Provides the SearchCriteria class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/


/**
* SearchCriteria
*
* 
* @package Geograph
*/
class SearchCriteria
{
	var $db=null;
	
	/**
	* text representing this search
	*/
	var $searchq;
	
	/**
	* centeroid of search (supplied or calculated)
	*/
	var $x;
  	var $y;
	
	var $resultsperpage;
	var $displayclass;
	
	var $is_multiple = false;
	
	var $sphinx = array(
		'query' => '',
		'sort' => '@relevance DESC, @id DESC',
		'impossible' => 0,
		'compatible' => 1,
		'compatible_order' => 1,
		'no_legacy' => 0,
		'filters' => array()
	);
	var $sql = array(
		'fields' => '',
		'from' => '',
		'where' => '',
		'order' => ''
	);
	
	function compact() {
		unset($this->db);
		unset($this->is_multiple);
		unset($this->sphinx);
		unset($this->sql);
		unset($this->crt_timestamp_ts);
	}
	
	function toDays($date) {
		$db = $this->_getDB(true);
		$date = str_replace('-00','-01',$date);
		return intval($db->GetOne('select to_days('.
			(preg_match('/\)$/',$date)?$date:$db->Quote($date)).
			')'));
	}
	
	function getSQLParts() 
	{
		global $CONF;

		extract($this->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');

		if (!empty($_GET['BBOX'])) {
			
			//we need to turn this off as it will break the caching of number of results!
			$CONF['search_count_first_page'] = 0;
			
			list($west,$south,$east,$north) = explode(',',trim(str_replace('e ','e+',$_GET['BBOX'])));
		
			if (!empty($_GET['LOOKAT'])) {
			
			
				// calculate the approx center of the view -- note that this is innaccurate if the user is not looking straight down
				$clong = (($east - $west)/2) + $west;
				$clat = (($north - $south)/2) + $south;
			
				list($long,$lat) = preg_split('/,|\s/', str_replace('e ','e+',$_GET['LOOKAT']));
							
				
				$uplat = ($clat + $north) / 2.0;
				$lolat = ($south + $clat) / 2.0;
			
				//is the lookat point outside the central square of the BBOX (hence large tilt)
				if ($lat > $uplat) {
					$diflat = ($north - $lat);
				} elseif ($lat < $lolat) {
					$diflat = ($lat - $south);		
				}
				
				$uplong = ($clong + $east) / 2.0;
				$lolong = ($west + $clong) / 2.0;
			
				if ($long > $uplong) {
					$diflong = ($east - $long);
				} elseif ($long < $lolong) {
					$diflong = ($long - $west);
				}
				
				//find a suitable 'distance' from an edge
				$dif = abs(max($diflat,$diflong));
				
				//if we have an off center view create a new square and recenter it 'in the foreground' 
				if ($dif) {
					function interpolate_part($one,$two,$fraction) {
						$big = $two - $one;
						$small = $fraction * $big;
						return $one + $small;
					}
			
					$linelength = sqrt(pow($lat - $clat,2) + pow($long - $clong,2));
					$fraction = $dif / $linelength;
			
					//find the point on the line between the lookat and the center point
					$nlat = interpolate_part($lat,$clat, $fraction);
					$nlong = interpolate_part($long,$clong, $fraction);
			
					//and recenter the 'square' on that new point
					$south = $nlat - $dif;
					$north = $nlat + $dif;
			
					$west = $nlong - $dif;
					$east = $nlong + $dif;
				}
			}
			
			$span = max($east - $west,$north - $south); 
			
			if ($span > 8) {
				$sql_where = ''; //outside our area, so return unfiltered
			} else {			
				$conv = new ConversionsLatLong;

				list($e1,$n1,$ri1) = $conv->wgs84_to_national($south,$west,false);
				list($e2,$n2,$ri2) = $conv->wgs84_to_national($north,$east,false);

				if (!$ri1 || !$ri2) {
					$sql_where = ''; //outside our area, so return unfiltered
				} else {
					$rectangle = "'POLYGON(($west $south,$east $south,$east $north,$west $north,$west $south))'";

					$sql_where = "CONTAINS(GeomFromText($rectangle),point_ll)";
					
					if ($ri1 == $ri2) {
						//now possible, but calculate it JIT
						$this->sphinx['bbox'] = array($e1,$n1,$ri1,$e2,$n2,$ri2);
					} else {
						$this->sphinx['impossible']++;
					}
				}
			}
		} else {
			$sql_where = '';
		}
		
		$x = $this->x;
		$y = $this->y;
		if (!empty($x) && !empty($y)) {
			if ($this->limit8 && $this->limit8 < 2000 && $this->limit8 > -2000) {//2000 is a special value for effectivly unlimted!
				$d = abs(intval($this->limit8));
				if ($sql_where) {
					$sql_where .= ' and ';
				}

				if ($this->limit8 == 1) {
					$sql_where .= "CONTAINS( GeomFromText('POINT($x $y)'),point_xy )";
				} else {
					$left=$x-$d;
					$right=$x+$d-1;
					$top=$y+$d-1;
					$bottom=$y-$d;

					$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

					$sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";
					if ($this->limit8 > 0) {
						//shame cant use dist_sqd in the next line!
						$sql_where .= " and ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d);
					}
				}
			}
			if ($this->limit8 && $this->limit8 <= 20 && $this->limit8 > -20) { //todo - increase this? sphinx could use hectads
				//possible, but calculate it JIT
				$this->sphinx['x'] = $x;
				$this->sphinx['y'] = $y;
				$this->sphinx['d'] = $this->limit8;
				
				$this->sphinx['sort'] = "@geodist ASC, @relevance DESC, @id DESC";
			} else {
				$this->sphinx['impossible']++;
			}
			if ($this->limit8 == 1) {
				$sql_fields .= ", 0 as dist_sqd";
			} else {
				//not using "power(gs.x -$x,2) * power( gs.y -$y,2)" beucause is testing could be upto 2 times slower!
				$sql_fields .= ", ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) as dist_sqd";
				$sql_order = ' dist_sqd ';
			}
		} 
		if ((($x == 0 && $y == 0 ) || $this->limit8) && $this->orderby) {
			switch ($this->orderby) {
				case 'random':
					$sql_order = ' crc32(concat("'.($this->crt_timestamp_ts).'",gi.gridimage_id)) ';
					$this->sphinx['compatible_order'] = 0;
					$this->sphinx['sort'] = "@random";
					break;
				case 'dist_sqd':
					break;
				case 'relevance':
					$sql_order = '';
					$this->sphinx['sort'] = "@relevance DESC, @id DESC";
					break;
				case 'imagetaken':
					if ($sql_where) {
						$sql_where .= ' and ';
					}
					$sql_where .= "imagetaken NOT LIKE '0000-%'";
					//falls though...
				default:
					$sql_order = preg_replace('/[^\w,\(\)]+/',' ',$this->orderby);
					
					switch (str_replace(' desc','',$this->orderby)) {
						case 'gridimage_id':
						case 'submitted': 
							$this->sphinx['sort'] = '@id';
							break;
						case 'x':
							$this->sphinx['compatible_order'] = 0;
							$this->sphinx['sort'] = 'wgs84_long';
							break;
						case 'y':
							$this->sphinx['compatible_order'] = 0;
							$this->sphinx['sort'] = 'wgs84_lat';
							break;
						case 'imagetaken':
							$this->sphinx['compatible_order'] = 0;
							$this->sphinx['sort'] = 'takendays';
							break;
						case 'realname':
						case 'title':
						case 'imageclass':
						case 'grid_reference':
						default: 
							$this->sphinx['impossible']++;
					}
					if (!$this->sphinx['impossible'] && preg_match('/ desc$/',$this->orderby)) {
						$this->sphinx['sort'] .= " DESC";
					} else {
						$this->sphinx['sort'] .= " ASC";
					}
			}
			$sql_order = preg_replace('/^submitted/','gridimage_id',$sql_order);
		} elseif (empty($this->sphinx['sort']) || $this->sphinx['sort'] != "@geodist ASC, @relevance DESC, @id DESC") {
			//sphinx undefined is 'relevence' where mysql undefined is table order
			$this->sphinx['compatible_order']=0;
		}
		if ($this->breakby) {
			switch (str_replace(' desc','',$this->breakby)) {
				case 'gridimage_id':
				case 'submitted': 
					$breakby = 'gridimage_id';
					$sorder = '@id';
					break;
				case 'x':
					$this->sphinx['compatible_order'] = 0;
					$sorder = 'wgs84_long';
					break;
				case 'y':
					$this->sphinx['compatible_order'] = 0;
					$sorder = 'wgs84_lat';
					break;
				case 'imagetaken':
				case 'imagetaken_month':
				case 'imagetaken_year':
				case 'imagetaken_decade':
					$breakby = 'imagetaken';
					if (strpos($sql_order,'imagetaken') === FALSE) {
						//todo - maybe remove this section, it probably has performance issues, and it still "works" if sorted just by date directly (that what sphinx does)
						switch ($breakby) {
							case 'imagetaken_month':
								$breakby = "SUBSTRING(imagetaken,1,7)";
								break;
							case 'imagetaken_year':
								$breakby = "SUBSTRING(imagetaken,1,4)";
								break;
							case 'imagetaken_decade':
								$breakby = "SUBSTRING(imagetaken,1,3)";
								break;
						}
					}
					$this->sphinx['compatible_order'] = 0;
					$sorder = 'takendays';
					break;
				case 'imageclass':
					$sorder = 'classcrc';
					break;
				case 'realname':
				case 'title':
				case 'grid_reference':
				default: 
					$this->sphinx['impossible']++;
			}
			
			if (strpos($sql_order,' desc') !== FALSE) {
				$breakby .= ' desc';
				$sorder2 = " DESC";
			} else {
				$sorder2 = " ASC";
			}
			
			if ($breakby != $sql_order && !preg_match('/^(\w+)\+$/i',$this->breakby) ) {
				$sql_order = $breakby.($sql_order?", $sql_order":'');
				$this->sphinx['sort'] = "$sorder $sorder2".($this->sphinx['sort']?", {$this->sphinx['sort']}":'');
			}
		}
		
		$sql_where_start = $sql_where;
		
		
		$this->getSQLPartsFromText($this->searchtext);
		
		
		if (!empty($this->limit1)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			if (strpos($this->limit1,'!') === 0) {
				$sql_where .= 'gi.user_id != '.preg_replace('/^!/','',$this->limit1);
			} else {
				$sql_where .= 'gi.user_id = '.($this->limit1);
			}
			$this->sphinx['filters']['user_id'] = $this->limit1;
		} 
		if (!empty($this->limit2)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$statuslist="'".implode("','", explode(',',$this->limit2))."'";
			$sql_where .= "moderation_status in ($statuslist) ";
			if ($this->limit2 == 'geograph') {
				$this->sphinx['filters']['status'] = $this->limit2;
			} elseif ($this->limit2 == 'accepted') {
				$this->sphinx['filters']['status'] = 'supplemental';
			}
		} 
		if (!empty($this->limit3)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$sql_where .= "imageclass = '".addslashes(($this->limit3 == '-')?'':$this->limit3)."' ";
			//todo tags tags tags
			
			if ($this->limit3 == '-') {
				$this->sphinx['impossible']++;
			} else {
				#$this->sphinx['filters']['imageclass'] = "\"".$this->limit3."\"";
				$db = $this->_getDB(true);
				$this->sphinx['filters']['classcrc'] = array($db->GetOne('select crc32(lower('.$db->Quote($this->limit3).'))'));
			}
		} 
		if (!empty($this->limit4)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$sql_where .= 'gs.reference_index = '.($this->limit4).' ';
			
			if (empty($this->sphinx['d'])) {//no point adding this filter if querying on location!
				$square=new GridSquare;
				$prefixes = $square->getGridPrefixes($this->limit4);
				$this->sphinx['filters']['myriad'] = "(".implode(' | ',$prefixes).")";
			}
		} 
		if (!empty($this->limit5)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			
			$db = $this->_getDB(true);
			
			$prefix = $db->GetRow('select * from gridprefix where prefix='.$db->Quote($this->limit5).' limit 1');	
			
			$left=$prefix['origin_x'];
			$right=$prefix['origin_x']+$prefix['width']-1;
			$top=$prefix['origin_y']+$prefix['height']-1;
			$bottom=$prefix['origin_y'];
			
			$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

			$sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";
			
			if (empty($this->limit4))
				$sql_where .= ' and gs.reference_index = '.$prefix['reference_index'].' ';
			
			$this->sphinx['filters']['myriad'] = $this->limit5;
		}
		if (!empty($this->limit6)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$dates = explode('^',$this->limit6);
			
			//if a 'to' search then we must make blank bits match the end!
			list($y,$m,$d) = explode('-',$dates[1]);
			if ($y > 0) {
				if ($m == 0) {
					$m = 12;
				}  
				if ($d == 0) {
					$d = date('t',mktime(0,0,0,$m,1,$y)); ;
				}
				$dates[1] = "$y-$m-$d";
			}
			
			if ($dates[0]) {
				if (preg_match("/0{4}-([01]?[1-9]+|10)-/",$dates[0]) > 0) {
					//month only
					list($y,$m,$d) = explode('-',$dates[0]);
					$sql_where .= "MONTH(submitted) = $m ";
					
					$this->sphinx['impossible']++;
				} elseif (preg_match("/0{4}-0{2}-([01]?[1-9]+|10)/",$dates[0]) > 0) {
					//day only ;)
					list($y,$m,$d) = explode('-',$dates[0]);
					$sql_where .= "submitted > DATE_SUB(NOW(),INTERVAL $d DAY)";
					
					$this->sphinx['filters']['submitted'] = array(time()-86400*$d,time()); 
				} elseif ($dates[1]) {
					if ($dates[0] == $dates[1]) {
						//both the same
						$sql_where .= "submitted LIKE '".$dates[0]."%' ";
						$this->sphinx['filters']['submitted'] = array(strtotime($dates[0]),strtotime($dates[0]." 23:59:59")); 
					} else {
						//between
						$sql_where .= "submitted BETWEEN '".$dates[0]."' AND DATE_ADD('".$dates[1]."',INTERVAL 1 DAY) ";
						$this->sphinx['filters']['submitted'] = array(strtotime($dates[0]),strtotime($dates[1]." 23:59:59")); 
					}
				} else {
					//from
					$sql_where .= "submitted >= '".$dates[0]."' ";
					$this->sphinx['filters']['submitted'] = array(strtotime($dates[0]),time()); 
				}
			} else {
				//to
				$sql_where .= "submitted <= '".$dates[1]."' ";
				$this->sphinx['filters']['submitted'] = array(strtotime("2005-01-01"),strtotime($dates[1]." 23:59:59")); 
			}
			
			
		}	
		if (!empty($this->limit7)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$dates = explode('^',$this->limit7);
			
			$same = ($dates[0] == $dates[1]);
			
			//if a 'to' search then we must make blank bits match the end!
			list($y1,$m1,$d1) = explode('-',$dates[1]);
			if ($y1 > 0) {
				if ($m1 == 0) {
					$m1 = 12;
				}
				if ($d1 == 0) {
					$d1 = date('t',mktime(0,0,0,$m1,1,$y1));
				}
				$dates[1] = sprintf('%04d-%02d-%02d',$y1,$m1,$d1);
			}
			
			
			if ($dates[0]) {
				list($y,$m,$d) = explode('-',$dates[0]);
				$days0 = $this->toDays($dates[0]);
				if (preg_match("/0{4}-([01]?[1-9]+|10)-/",$dates[0]) > 0) {
					//month only
					$sql_where .= "MONTH(imagetaken) = $m ";
					
					$db = $this->_getDB(true);
					$this->sphinx['filters']['month'] = $db->GetOne("SELECT MONTHNAME('2001-$m-01')");
			
				} elseif (preg_match("/0{4}-0{2}-([01]?[1-9]+|10)/",$dates[0]) > 0) {
					//day only ;)
					$sql_where .= "imagetaken > DATE_SUB(NOW(),INTERVAL $d DAY)";
					$start = $this->toDays("DATE_SUB(NOW(),INTERVAL $d DAY)");
					$now = $this->toDays('NOW()');
					$this->sphinx['filters']['takendays'] = array($start,$now);
				} elseif ($dates[1]) {
					if ($same) {
						//both the same
						if ($m == 0) {
							$sql_where .= "imagetaken LIKE '$y%' ";
							$this->sphinx['filters']['takenyear'] = $y;
						} elseif ($d == 0) {
							$sql_where .= "imagetaken = '".sprintf('%04d-%02d',$y,$m)."%' ";
							$this->sphinx['filters']['takenmonth'] = sprintf('%04d%02d',$y,$m);
						} else {
							$sql_where .= "imagetaken = '".$dates[0]."' ";
							$this->sphinx['filters']['takenday'] = str_replace('-','',$dates[0]);
						}
					} else {
						//between
						$sql_where .= "imagetaken BETWEEN '".$dates[0]."' AND '".$dates[1]."' ";
						$days1 = $this->toDays($dates[1]);
						$this->sphinx['filters']['takendays'] = array($days0,$days1); 
					}
				} else {
					//from
					$sql_where .= "imagetaken >= '".$dates[0]."' ";
					$now = $this->toDays('NOW()');
					$this->sphinx['filters']['takendays'] = array($days0,$now); 
				}
			} else {
				//to
				$sql_where .= "imagetaken != '0000-00-00' AND imagetaken <= '".$dates[1]."' ";
				$days1 = $this->toDays($dates[1]);
				$this->sphinx['filters']['takendays'] = array(1,$days1); //1 is just so doesnt match 0
			}
			
			
		}
		
		if (!empty($this->limit9)) {
			if ($this->limit9 > 1) {
				if ($sql_where)
					$sql_where .= ' and ';
				$sql_where .= "topic_id = {$this->limit9} ";
			}
			$sql_from .= " INNER JOIN gridimage_post gp ON(gi.gridimage_id=gp.gridimage_id) ";
			$this->sphinx['impossible']++;
		} 
		if (!empty($this->limit10)) {
			if ($sql_where)
				$sql_where .= ' and ';
			$sql_where .= "route_id = {$this->limit10} and ftf=1 ";
			$sql_from .= " INNER JOIN route_item r ON(grid_reference=r.gridref) ";
			$this->sphinx['impossible']++;
		} 
		
		if ($sql_where_start != $sql_where) {
			$this->issubsetlimited = true;
		}
		if (!empty($_GET['debug'])) { 
			print "<pre>";
			print_r($this->sql);
			print_r($this->sphinx);
			print "</pre>";
		}
	} 
	
	function getSQLPartsFromText($q) {
		if (empty($q)) 
			return;
		$db = $this->_getDB(true);
		
		extract($this->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');
		
		if ($sql_where) {
			$sql_where .= ' and ';
		}
		if (preg_match('/\b=\w/',$q)) {
			// = in latest sphinx turns on exact keyword matching (no stemming) 
			$this->sphinx['compatible'] = 0;
		}
		if (preg_match("/\b(AND|OR|NOT)\b/",$q) || preg_match('/^\^.*\+$/',$q) || preg_match('/(^|\s+)-([\w^]+)/',$q)) {
			$sql_where .= " (";
			$terms = $prefix = $postfix = '';
			$tokens = preg_split('/\s+/',trim(preg_replace('/([\(\)])/',' $1 ',preg_replace('/(^|\s+)-([\w^]+)/e','("$1"?"$1AND ":"")."NOT $2"',$q))));
			$number = count($tokens);
			$c = 1;
			$tokens[] = 'END';
			foreach ($tokens as $token) {
				switch ($token) {
					case 'END': $token = '';
					case 'AND':
					case 'OR': 
						if ($c != 1 && $c != $number) {
							if (strpos($terms,'^') === 0) {
								$words = 'REGEXP '.$db->Quote('[[:<:]]'.str_replace('^','',preg_replace('/\+$/','',$terms)).'[[:>:]]');
							} else {
								$words = 'LIKE '.$db->Quote('%'.preg_replace('/[\+~]$/','',$terms).'%');
							}
							
							if (preg_match('/\~$/',$terms)) {								
								$sql_where .= " $prefix (gi.title ".$words.' OR gi.comment '.$words.')';
							} elseif (preg_match('/\+$/',$terms)) {								
								$sql_where .= " $prefix (gi.title ".$words.' OR gi.comment '.$words.' OR gi.imageclass '.$words.')';
							} else {
								$sql_where .= " gi.title $prefix ".$words;
							}
							$sql_where .= " $postfix $token ";
							$terms = $prefix = $postfix = '';
						}
						break;
					case '(': 
						$sql_where .= " $prefix $token";
						$prefix = '';
						break;
					case ')': 
						$postfix = $token;
						break;
					case 'NOT': $prefix = 'NOT'; break;
					default: 
						if ($terms)	$terms .= " ";
						$terms .= $token;
				}
				$c++;
			}
			$sql_where .= ")";
			
			if (preg_match('/^\^([\w ]+)\+$/',$q,$m) && !preg_match("/\b(AND|OR|NOT)\b/",$m[1])) { //convert to a phrase search
				$this->sphinx['query'] .= " \"".preg_replace('/[\+^]+/','',$q)."\"";
				$this->sphinx['no_legacy']++;
			} elseif (preg_match('/[:@"]/',$q)) { //already in sphinx format!
				$this->sphinx['query'] .= " ".$q;
				$this->sphinx['no_legacy']++;
			} else {
				$this->sphinx['query'] .= " ".preg_replace('/[\+^]+/','',str_replace("NOT ",' -',str_replace(" AND ",' ',$q)));
			}
			
		} elseif (strpos($q,'^') === 0) {
			$words = str_replace('^','',$q);
			$sql_where .= ' title REGEXP '.$db->Quote('[[:<:]]'.preg_replace('/\+$/','',$words).'[[:>:]]');
			$this->sphinx['query'] .= " ^".$words;
		} elseif (preg_match('/\+$/',$q)) {
			$words = $db->Quote('%'.preg_replace("/\+$/",'',$q).'%');
			$sql_where .= ' (gi.title LIKE '.$words.' OR gi.comment LIKE '.$words.' OR gi.imageclass LIKE '.$words.')';
			$this->sphinx['query'] .= " ".preg_replace("/\+$/",'',$q);
			$this->isallsearch = 1;
		} elseif (preg_match('/[:@]/',$q)) {
			$sql_where .= ' gi.title LIKE '.$db->Quote('%'.$q.'%');//todo, maybe better handle this - jsut for legacy searches...
			$this->sphinx['query'] .= " ".$q;
			$this->sphinx['no_legacy']++;
		} else {
			$sql_where .= ' gi.title LIKE '.$db->Quote('%'.$q.'%');
			$this->sphinx['query'] .= " ".$q; //todo this is defaulting to searching all 
			$this->changeindefault = 1;
		}
		$this->sphinx['query'] = preg_replace('/\b(day|month|year):/','taken$1:',$this->sphinx['query']);
		$this->sphinx['query'] = preg_replace('/\b(gridref):/','grid_reference:',$this->sphinx['query']);
		$this->sphinx['query'] = preg_replace('/\b(category):/','imageclass:',$this->sphinx['query']);
		$this->sphinx['query'] = preg_replace('/\b(description):/','comment:',$this->sphinx['query']);
		$this->sphinx['query'] = preg_replace('/\b(name):/','realname:',$this->sphinx['query']);
		if (strlen($this->sphinx['query'])) {
			//really there is little chance its going to be compatible... 
			$this->sphinx['compatible'] = 0;
		}
	}
	
	function countSingleSquares($radius = 4) {
		global $CONF;
		$db = $this->_getDB(true);
		
		$x = $this->x;
		$y = $this->y;
		
		$left=$x-$radius;
		$right=$x+$radius;
		$top=$y-$radius;
		$bottom=$y+$radius;

		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

		$sql="select count(*) 
			from gridsquare where
			CONTAINS( 	
				GeomFromText($rectangle),
				point_xy)
			AND imagecount<2
			AND power(x-$x,2)+power(y-$y,2) <= ($radius*$radius)
			AND percent_land>0";
		
		return $db->getOne($sql);
	}
	
	/**
	* return true if instance references a valid search
	*/
	function isValid()
	{
		return isset($this->searchq);
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
			if ($name!='db')
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
	function &_getDB($allow_readonly = false)
	{
		//check we have a db object or if we need to 'upgrade' it
		if (!is_object($this->db) || ($this->db->readonly && !$allow_readonly) ) {
			$this->db=GeographDatabaseConnection($allow_readonly);
		}
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

class SearchCriteria_GridRef extends SearchCriteria
{
	
}

class SearchCriteria_Special extends SearchCriteria
{
	function getSQLParts() {
		parent::getSQLParts();
		
		extract($this->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');
		
		if ($sql_where) {
			$sql_where .= ' and ';
		}
		$sql_where .= $this->searchq;
		$this->sphinx['impossible']++; //todo, safest - but could do some?
		
		if (preg_match("/group by ([\w\,\(\)\/ ]+)/i",$sql_where,$matches)) {
			$this->sphinx['impossible']++; //todo, safest - but could do some?
		
		} elseif (preg_match("/(left |inner |)join ([\w\,\(\) \.\'!=`]+) where/i",$sql_where,$matches)) {
			$this->sphinx['impossible']++; //will never be possible?
		}
		
		if (preg_match('/^(\w+)\+$/i',$this->breakby,$matches) && !in_array($matches[1],array('email','password','age_group')) ) { #todo untimately check what table it comes from
			$sql_fields .= ", ".$matches[1];
		}
	}
}

class SearchCriteria_Text extends SearchCriteria
{
	function getSQLParts() {
		parent::getSQLParts();
		
		$this->getSQLPartsFromText($this->searchq);
	}
}

class SearchCriteria_All extends SearchCriteria
{
	/*
	* allows finding of a user by text string
	*/
	function setByUsername($username) {
		$db = $this->_getDB(true);
		if (preg_match('/^(\d+):/',$username,$m)) {
			$users = $db->GetAll("select user_id,realname,nickname from user where user_id={$m[1]} limit 2");
		} elseif (!preg_match('/\bnear\b/',$username)) {
			$username2 = $db->Quote($username);
			$users = $db->GetAll("select user_id,realname,nickname from user inner join user_stat using (user_id) where rights LIKE '%basic%' AND MATCH (realname,nickname) AGAINST ($username2) order by (nickname=$username2 or realname=$username2) desc limit 2");
		}
		if (count($users) == 1 || 
			( count($users) && 
				(strcasecmp($users[0]['realname'],$username) == 0 || strcasecmp($users[0]['nickname'],$username) == 0 )
			) 
		) {
			$this->realname = $users[0]['realname'];
			$this->user_id = $users[0]['user_id'];
			if (strcasecmp($username,$this->realname) != 0) {
				$this->nickname = $users[0]['nickname'];
			}
		}
	}
	
}

class SearchCriteria_Placename extends SearchCriteria
{
	var $matches;
	var $placename;
	
	function compact() {
		parent::compact();
		
		unset($this->matches);
		unset($this->placename);
	}
	
	function setByPlacename($placename) {
		$gaz = new Gazetteer();
		
		$this->ismore = (strpos($placename,'?') !== FALSE);
		
		$places = $gaz->findPlacename($placename);
		
		if (count($places) == 1) {
			$db = $this->_getDB(true);
			$origin = $db->CacheGetRow(100*24*3600,"select origin_x,origin_y from gridprefix where reference_index=".$places[0]['reference_index']." and origin_x > 0 order by origin_x,origin_y limit 1");	

			$this->x = intval($places[0]['e']/1000) + $origin['origin_x'];
			$this->y = intval($places[0]['n']/1000) + $origin['origin_y'];
			$this->placename = $places[0]['full_name'].($places[0]['adm1_name']?", {$places[0]['adm1_name']}":'');
			$this->searchq = $placename;
			$this->full_name = $places[0]['full_name'];
			$this->reference_index = $places[0]['reference_index'];
			$this->_matches = $places;
		} elseif (count($places)) {
			$this->matches = $places;
			$this->is_multiple = true;
			$this->searchq = $placename;
		}
	}
}

class SearchCriteria_Postcode extends SearchCriteria
{
	function setByPostcode($code) {
		$db = $this->_getDB(true);
		if (strpos($code,' ') === FALSE) {
			//yes know avg(reference_index) is always same as reference_index, but get round restriction in mysql
			$postcode = $db->GetRow('select avg(e) as e,avg(n) as n,avg(reference_index) as reference_index from loc_postcodes where code like'.$db->Quote("$code _").'');			
		} else {
			$postcode = $db->GetRow('select e,n,reference_index from loc_postcodes where code='.$db->Quote($code).' limit 1');	
		}
		if ($postcode['reference_index']) {
			$origin = $db->CacheGetRow(100*24*3600,'select origin_x,origin_y from gridprefix where reference_index='.$postcode['reference_index'].' and origin_x > 0 order by origin_x,origin_y limit 1');	

			$this->x = intval($postcode['e']/1000) + $origin['origin_x'];
			$this->y = intval($postcode['n']/1000) + $origin['origin_y'];
			$this->reference_index = $postcode['reference_index'];
		}
	}
}

class SearchCriteria_County extends SearchCriteria
{
	var $county_name;
	
	function compact() {
		parent::compact();
		
		unset($this->county_name);
	}
	
	function setByCounty($county_id) {
		$db = $this->_getDB(true);
		
		$county = $db->GetRow('select e,n,name,reference_index from loc_counties where county_id='.$db->Quote($county_id).' limit 1');	
	
		//get the first gridprefix with the required reference_index
		//after ordering by x,y - you'll get the bottom
		//left gridprefix, and hence the origin

		$origin = $db->CacheGetRow(100*24*3600,'select origin_x,origin_y from gridprefix where reference_index='.$county['reference_index'].' and origin_x > 0 order by origin_x,origin_y limit 1');	

		$this->x = intval($county['e']/1000) + $origin['origin_x'];
		$this->y = intval($county['n']/1000) + $origin['origin_y'];
		$this->county_name = $county['name'];
		$this->reference_index = $county['reference_index'];
	}
}

?>
