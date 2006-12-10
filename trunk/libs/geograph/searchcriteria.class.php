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
	
	
	function getSQLParts(&$sql_fields,&$sql_order,&$sql_where,&$sql_from) 
	{
		if (!empty($_GET['BBOX'])) {
			list($left,$bottom,$right,$top) = explode(',',trim(str_replace('e ','e+',$_GET['BBOX'])));
			
			$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

			$sql_where = "CONTAINS(GeomFromText($rectangle),point_ll)";
		} else {
			$sql_where = '';
		}
		
		$x = $this->x;
		$y = $this->y;
		if ($x > 0 && $y > 0) {
			if ($this->limit8 && $this->limit8 < 2000) {//2000 is a special value for effectivly unlimted!
				$d = intval($this->limit8);
				if ($sql_where) {
					$sql_where .= ' and ';
				}

				$left=$x-$d;
				$right=$x+$d;
				$top=$y+$d;
				$bottom=$y-$d;

				$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

				$sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";

				//shame cant use dist_sqd in the next line!
				$sql_where .= " and ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d);
			}

			//not using "power(gs.x -$x,2) * power( gs.y -$y,2)" beucause is testing could be upto 2 times slower!
			$sql_fields .= ", ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) as dist_sqd";
			$sql_order = ' dist_sqd ';
		} 
		if ((($x == 0 && $y == 0 ) || $this->limit8) && $this->orderby) {
			switch ($this->orderby) {
				case 'random':
					$sql_order = ' rand('.($this->crt_timestamp_ts).') ';
					break;
				case 'dist_sqd':
					break;
				case 'imagetaken':
					if ($sql_where) {
						$sql_where .= ' and ';
					}
					$sql_where .= "imagetaken NOT LIKE '0000-%'";
					//falls though...
				default:
					$sql_order = $this->orderby;
			}
			$sql_order = preg_replace('/^submitted/','gridimage_id',$sql_order);
		}
		
		$sql_where_start = $sql_where;
		
		if (!empty($this->limit1)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			if (strpos($this->limit1,'!') === 0) {
				$sql_where .= 'gi.user_id != '.preg_replace('/^!/','',$this->limit1);
			} else {
				$sql_where .= 'gi.user_id = '.($this->limit1);
			}
		} 
		if (!empty($this->limit2)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$statuslist="'".implode("','", explode(',',$this->limit2))."'";
			$sql_where .= "moderation_status in ($statuslist) ";
		} 
		if (!empty($this->limit3)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$sql_where .= "imageclass = '".addslashes(($this->limit3 == '-')?'':$this->limit3)."' ";
		} 
		if (!empty($this->limit4)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$sql_where .= 'gs.reference_index = '.($this->limit4).' ';
		} 
		if (!empty($this->limit5)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			
			$db = $this->_getDB();
			
			$prefix = $db->GetRow('select * from gridprefix where prefix='.$db->Quote($this->limit5).' limit 1');	
			
			$left=$prefix['origin_x'];
			$right=$prefix['origin_x']+$prefix['width']-1;
			$top=$prefix['origin_y']+$prefix['height']-1;
			$bottom=$prefix['origin_y'];
			
			$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

			$sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";
			
			if (empty($this->limit4))
				$sql_where .= ' and gs.reference_index = '.$prefix['reference_index'].' ';
			
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
					$m = 12; $d = 31;
				} else if ($d == 0) {
					$d = 31;
				}
				$dates[1] = "$y-$m-$d";
			}
			
			if ($dates[0]) {
				if (preg_match("/0{4}-([01]?[1-9]+|10)-/",$dates[0]) > 0) {
						//month only
						list($y,$m,$d) = explode('-',$dates[0]);
						$sql_where .= "MONTH(submitted) = $m ";
				} elseif (preg_match("/0{4}-0{2}-([01]?[1-9]+|10)/",$dates[0]) > 0) {
						//day only ;)
						list($y,$m,$d) = explode('-',$dates[0]);
						$sql_where .= "submitted > DATE_SUB(NOW(),INTERVAL $d DAY)";
				} elseif ($dates[1]) {
					if ($dates[0] == $dates[1]) {
						//both the same
						$sql_where .= "submitted LIKE '".$dates[0]."%' ";
					} else {
						//between
						$sql_where .= "submitted BETWEEN '".$dates[0]."' AND DATE_ADD('".$dates[1]."',INTERVAL 1 DAY) ";
					}
				} else {
					//from
					$sql_where .= "submitted >= '".$dates[0]."' ";
				}
			} else {
				//to
				$sql_where .= "submitted <= '".$dates[1]."' ";
			}
			
			
		}	
		if (!empty($this->limit7)) {
			if ($sql_where) {
				$sql_where .= ' and ';
			}
			$dates = explode('^',$this->limit7);
			
			//if a 'to' search then we must make blank bits match the end!
			list($y,$m,$d) = explode('-',$dates[1]);
			if ($y > 0) {
				if ($m == 0) {
					$m = 12; $d = 31;
				} else if ($d == 0) {
					$d = 31;
				}
				$dates[1] = "$y-$m-$d";
			}
			
			
			if ($dates[0]) {
				if (preg_match("/0{4}-([01]?[1-9]+|10)-/",$dates[0]) > 0) {
					//month only
					list($y,$m,$d) = explode('-',$dates[0]);
					$sql_where .= "MONTH(imagetaken) = $m ";
				} elseif (preg_match("/0{4}-0{2}-([01]?[1-9]+|10)/",$dates[0]) > 0) {
						//day only ;)
						list($y,$m,$d) = explode('-',$dates[0]);
						$sql_where .= "imagetaken > DATE_SUB(NOW(),INTERVAL $d DAY)";
				} elseif ($dates[1]) {
					if ($dates[0] == $dates[1]) {
						//both the same
						$sql_where .= "imagetaken = '".$dates[0]."' ";
					} else {
						//between
						$sql_where .= "imagetaken BETWEEN '".$dates[0]."' AND '".$dates[1]."' ";
					}
				} else {
					//from
					$sql_where .= "imagetaken >= '".$dates[0]."' ";
				}
			} else {
				//to
				$sql_where .= "imagetaken != '0000-00-00' AND imagetaken <= '".$dates[1]."' ";
			}
			
			
		}	
		
		if (!empty($this->limit9)) {
			if ($this->limit9 > 1) {
				if ($sql_where)
					$sql_where .= ' and ';
				$sql_where .= "topic_id = {$this->limit9} ";
			}
			$sql_from .= " INNER JOIN gridimage_post gp ON(gi.gridimage_id=gp.gridimage_id) ";
		} 
		if (!empty($this->limit10)) {
			if ($sql_where)
				$sql_where .= ' and ';
			$sql_where .= "route_id = {$this->limit10} and ftf=1 ";
			$sql_from .= " INNER JOIN route_item r ON(grid_reference=r.gridref) ";
		} 
		
		if ($sql_where_start != $sql_where) {
			$this->issubsetlimited = true;
		}
	} 
	
	function countSingleSquares() {
		global $CONF;
		$db = $this->_getDB();
		
		$x = $this->x;
		$y = $this->y;
		$radius = $CONF['search_prompt_radius'];
		
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

class SearchCriteria_GridRef extends SearchCriteria
{
	
}

class SearchCriteria_Special extends SearchCriteria
{
	function getSQLParts(&$sql_fields,&$sql_order,&$sql_where,&$sql_from) {
		parent::getSQLParts($sql_fields,$sql_order,$sql_where,$sql_from);
		if ($sql_where) {
			$sql_where .= ' and ';
		}
		$sql_where .= $this->searchq;
	}
}

class SearchCriteria_Text extends SearchCriteria
{
	function getSQLParts(&$sql_fields,&$sql_order,&$sql_where,&$sql_from) {
		parent::getSQLParts($sql_fields,$sql_order,$sql_where,$sql_from);
		$db = $this->_getDB();
		if ($sql_where) {
			$sql_where .= ' and ';
		}
		if (preg_match("/\b(AND|OR|NOT)\b/",$this->searchq) || preg_match('/^\^.*\+$/',$this->searchq) || preg_match('/(^|\s+)-([\w^]+)/',$this->searchq)) {
			$sql_where .= " (";
			$terms = $prefix = $postfix = '';
			$tokens = preg_split('/\s+/',trim(preg_replace('/([\(\)])/',' $1 ',preg_replace('/(^|\s+)-([\w^]+)/e','("$1"?"$1AND ":"")."NOT $2"',$this->searchq))));
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
		} elseif (strpos($this->searchq,'^') === 0) {
			$words = str_replace('^','',$this->searchq);
			$len = substr_count($words,' ')+1;
			if ($len >= 1 && $len <= 3) {
				$sql_where .= " wordnet$len.title>0 AND words = ".$db->Quote($words);
				$sql_from .= " INNER JOIN wordnet$len ON(gi.gridimage_id=wordnet$len.gid) ";
			} else {
				$sql_where .= ' title REGEXP '.$db->Quote('[[:<:]]'.preg_replace('/\+$/','',$words).'[[:>:]]');
			}
		} elseif (preg_match('/\+$/',$this->searchq)) {
			$words = $db->Quote('%'.preg_replace("/\+$/",'',$this->searchq).'%');
			$sql_where .= ' (gi.title LIKE '.$words.' OR gi.comment LIKE '.$words.' OR gi.imageclass LIKE '.$words.')';
		} else {
			$sql_where .= ' gi.title LIKE '.$db->Quote('%'.$this->searchq.'%');
		}
	}
}

class SearchCriteria_All extends SearchCriteria
{
	function getSQLParts(&$sql_fields,&$sql_order,&$sql_where,&$sql_from) {
		parent::getSQLParts($sql_fields,$sql_order,$sql_where,$sql_from);
		
		if (!$this->orderby)
			$sql_order .= " rand('{$this->crt_timestamp_ts}') ";
	}
	
	/*
	* allows finding of a user by text string
	*/
	function setByUsername($username) {
		$db = $this->_getDB();
		if (preg_match('/^(\d+):/',$username,$m)) {
			$users = $db->GetAll("select user_id,realname from user where user_id={$m[1]} limit 2");			
		} else {
			$username = $db->Quote($username);
			$users = $db->GetAll("select user_id,realname from user where realname=$username or nickname=$username order by (nickname=$username) desc limit 2");
		}
		if (count($users) == 1) {
			$this->realname = $users[0]['realname'];
			$this->user_id = $users[0]['user_id'];
		}
	}
	
}

class SearchCriteria_Placename extends SearchCriteria
{
	var $matches;
	var $placename;
	
	//todo need to be moved to gazetteer class...
	function setByPlacename($placename) {
		global $places; //only way to get the array into the compare functions
		global $USER;
		$db = $this->_getDB();

		$ismore = 0;
		$placename = str_replace('?','',$placename,$ismore);
		$this->ismore = $ismore;
		
		if (is_numeric($placename)) {
			if ($placename > 1000000) {
				$places = $db->GetAll("select `def_nam` as full_name,'PPL' as dsg,`east` as e,`north` as n,1 as reference_index,`full_county` as adm1_name from os_gaz where seq=".$db->Quote($placename-1000000));
			} else {
				$places = $db->GetAll("select full_name,dsg,e,n,reference_index from loc_placenames where id=".$db->Quote($placename));
			}
		} elseif (!$ismore) {
			list($placename,$county) = preg_split('/\s*,\s*/',$placename);
			
			if (!empty($county)) {
				$qcount = $db->Quote($county);
				
				$places = $db->GetAll("select `def_nam` as full_name,'PPL' as dsg,`east` as e,`north` as n,1 as reference_index,`full_county` as adm1_name from os_gaz where def_nam=".$db->Quote($placename)." and (full_county = $qcount OR hcounty = $qcount)");
			} else {
			
				//todo need to 'union'  with other gazetterr! (as if one match in each then will no work!) 
				$places = $db->GetAll("select `def_nam` as full_name,'PPL' as dsg,`east` as e,`north` as n,1 as reference_index,`full_county` as adm1_name from os_gaz where def_nam=".$db->Quote($placename));
				if (count($places) == 0) {
					$places = $db->GetAll("select full_name,dsg,e,n,reference_index from loc_placenames where full_name=".$db->Quote($placename));
				}				
			}
		}
		
		if (count($places) == 1) {
			$origin = $db->CacheGetRow(100*24*3600,"select origin_x,origin_y from gridprefix where reference_index=".$places[0]['reference_index']." order by origin_x,origin_y limit 1");	

			$this->x = intval($places[0]['e']/1000) + $origin['origin_x'];
			$this->y = intval($places[0]['n']/1000) + $origin['origin_y'];
			$this->placename = $places[0]['full_name'].($county&&$places[0]['adm1_name']?", {$places[0]['adm1_name']}":'');
			$this->searchq = $places[0]['full_name'].($county&&$places[0]['adm1_name']?", {$places[0]['adm1_name']}":'');
			$this->reference_index = $places[0]['reference_index'];
		} else {
			$limit = (strlen($placename) > 3)?20:10;
			$limi2 = 10;
			if ($USER->registered) {
				$limit *= 2;
				$limi2 *= 2;
			}
			
			//starts with (both gaz's)
			$places = $db->GetAll("
			(select
				(seq + 1000000) as id,
				`def_nam` as full_name,
				'PPL' as dsg,`east` as e,`north` as n,
				code_name as dsg_name,
				1 as reference_index,
				`full_county` as adm1_name,
				km_ref as gridref
			from 
				os_gaz
				inner join os_gaz_code using (f_code)
			where
				os_gaz.f_code IN ('C','T','O') AND
				`def_nam` LIKE ".$db->Quote($placename.'%')."
			limit $limit) UNION
			(select 
				id, 
				full_name,
				dsg,e,n,
				loc_dsg.name as dsg_name,
				loc_placenames.reference_index,
				loc_adm1.name as adm1_name,
				'' as gridref
			from 
				loc_placenames
				inner join loc_dsg on (loc_placenames.dsg = loc_dsg.code) 
				left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and loc_placenames.reference_index = loc_adm1.reference_index)
			where
				dsg = 'PPL' AND loc_placenames.reference_index != 1 AND
				full_name LIKE ".$db->Quote($placename.'%')."
			LIMIT 20)");
			if (count($places) < 10 || $ismore) {
				//sounds like (OS)
				$places = array_merge($places,$db->GetAll("
				select
					(seq + 1000000) as id,
					`def_nam` as full_name,
					'PPL' as dsg,`east` as e,`north` as n,
					code_name as dsg_name,
					1 as reference_index,
					`full_county` as adm1_name,
					km_ref as gridref
				from 
					os_gaz
					inner join os_gaz_code using (f_code)
				where
					os_gaz.f_code IN ('C','T','O') AND
					def_nam_soundex = SOUNDEX(".$db->Quote($placename).") AND
					def_nam NOT LIKE ".$db->Quote($placename.'%')."
				limit $limi2"));
			}
			
			if (count($places) < 10 || $ismore) {
				//contains (OS)
				$places = array_merge($places,$db->GetAll("
				select
					(seq + 1000000) as id,
					`def_nam` as full_name,
					'PPL' as dsg,`east` as e,`north` as n,
					code_name as dsg_name,
					1 as reference_index,
					`full_county` as adm1_name,
					km_ref as gridref
				from 
					os_gaz
					inner join os_gaz_code using (f_code)
				where
					os_gaz.f_code IN ('C','T','O') AND
					`def_nam` LIKE ".$db->Quote('%'.$placename.'%')." AND
					`def_nam` NOT LIKE ".$db->Quote($placename.'%')."
				limit $limi2"));
			}
			
			if (count($places) < 10 || $ismore) {
				//search the widest possible (but exclude PPL's in GB)
				$places2 = $db->GetAll("
				(select
					(seq + 1000000) as id,
					`def_nam` as full_name,
					'PPL' as dsg,`east` as e,`north` as n,
					code_name as dsg_name,
					1 as reference_index,
					`full_county` as adm1_name,
					km_ref as gridref
				from 
					os_gaz
					inner join os_gaz_code using (f_code)
				where
					os_gaz.f_code NOT IN ('C','T','O') AND
					( `def_nam` LIKE ".$db->Quote('%'.$placename.'%')."
					OR def_nam_soundex = SOUNDEX(".$db->Quote($placename).") )
				limit $limi2) UNION
				(select 
					id, 
					full_name,
					dsg,e,n,
					loc_dsg.name as dsg_name,
					loc_placenames.reference_index,
					loc_adm1.name as adm1_name,
					'' as gridref
				from 
					loc_placenames
					inner join loc_dsg on (loc_placenames.dsg = loc_dsg.code) 
					left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and loc_placenames.reference_index = loc_adm1.reference_index)
				where
					(loc_placenames.reference_index != 1 || dsg != 'PPL') AND
					full_name LIKE ".$db->Quote('%'.$placename.'%')."
					OR full_name_soundex = SOUNDEX(".$db->Quote($placename).")
				LIMIT 20)");
				if (count($places2) && count($places)) {
					foreach ($places2 as $i2 => $place2) {
						$found = 0; $look = str_replace("-",' ',$place2['full_name']);
						foreach ($places as $i => $place) {
							if ($place['full_name'] == $look && $place['reference_index'] == $place2['reference_index']) {
								$found = 1; break;
							}
						}
						if (!$found) 
							array_push($places,$place2);
					}
				}
			}	
			if (count($places)) {
				require_once('geograph/conversions.class.php');
				$conv = new Conversions;
				foreach($places as $id => $row) {
					if (empty($row['gridref'])) {
						list($places[$id]['gridref'],) = $conv->national_to_gridref($row['e'],$row['n'],4,$row['reference_index']);
					}
				}
			
				$this->matches = $places;
				$this->is_multiple = true;
				$this->searchq = $placename;
			}
		}
	}
}

class SearchCriteria_Postcode extends SearchCriteria
{
	function setByPostcode($code) {
		$db = $this->_getDB();
		if (strpos($code,' ') === FALSE) {
			//yes know avg(reference_index) is always same as reference_index, but get round restriction in mysql
			$postcode = $db->GetRow('select avg(e) as e,avg(n) as n,avg(reference_index) as reference_index from loc_postcodes where code like'.$db->Quote("$code _").'');			
		} else {
			$postcode = $db->GetRow('select e,n,reference_index from loc_postcodes where code='.$db->Quote($code).' limit 1');	
		}
		if ($postcode['reference_index']) {
			$origin = $db->CacheGetRow(100*24*3600,'select origin_x,origin_y from gridprefix where reference_index='.$postcode['reference_index'].' order by origin_x,origin_y limit 1');	

			$this->x = intval($postcode['e']/1000) + $origin['origin_x'];
			$this->y = intval($postcode['n']/1000) + $origin['origin_y'];
			$this->reference_index = $postcode['reference_index'];
		}
	}
}

class SearchCriteria_County extends SearchCriteria
{
	var $county_name;
	function setByCounty($county_id) {
		$db = $this->_getDB();
		
		$county = $db->GetRow('select e,n,name,reference_index from loc_counties where county_id='.$db->Quote($county_id).' limit 1');	
	
		//get the first gridprefix with the required reference_index
		//after ordering by x,y - you'll get the bottom
		//left gridprefix, and hence the origin

		$origin = $db->CacheGetRow(100*24*3600,'select origin_x,origin_y from gridprefix where reference_index='.$county['reference_index'].' order by origin_x,origin_y limit 1');	

		$this->x = intval($county['e']/1000) + $origin['origin_x'];
		$this->y = intval($county['n']/1000) + $origin['origin_y'];
		$this->county_name = $county['name'];
		$this->reference_index = $county['reference_index'];
	}
}

?>
