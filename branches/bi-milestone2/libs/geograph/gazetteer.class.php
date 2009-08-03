<?php
/**
 * $Project: GeoGraph $
 * $Id: searchengine.class.php 2475 2006-09-04 13:23:46Z barry $
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
* Provides the Gazetteer class (Optimised for British Isles Use)
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision: 2475 $
*/


/**
* Gazetteer
*
* 
* @package Geograph
*/
class Gazetteer
{
	var $db=null;
	
	function findBySquare($square,$radius = 25000,$f_codes = null,$gazetteer = '') {
		return $this->findByNational($square->reference_index,$square->nateastings,$square->natnorthings,$radius,$f_codes,$gazetteer);
	}
	

	function findListByNational($reference_index,$e,$n,$radius = 1005) {
		global $CONF,$memcache;
		
		$mkey = "$reference_index,$e,$n,$radius,-".'.v3';//need to invalidate the whole cache. 
		//fails quickly if not using memcached!
		$places =& $memcache->name_get('g',$mkey);
		if ($places)
			return $places;
		
		$db=&$this->_getDB();
		
		$e = (floor($e/1000) * 1000) + 500;
		$n = (floor($n/1000) * 1000) + 500;
		
		//to optimise the query, we scan a square centred on the
		//the required point
		$left=$e-$radius;
		$right=$e+$radius;
		$top=$n-$radius;
		$bottom=$n+$radius;

		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

		if (($CONF['use_gazetteer'] == 'OS' || $CONF['use_gazetteer'] == 'OS250') && $reference_index == 1) {
			//even for 250k gaz, lets use the 50k as we want the detailed list
			
			$places = $db->GetAll("select
					`def_nam` as full_name,
					km_ref as grid_reference,
					'PPL' as dsg,
					1 as reference_index,
					`full_county` as adm1_name,
					`hcounty` as hist_county,
					(seq + 1000000) as pid,
					f_code,
					( (east-{$e})*(east-{$e})+(north-{$n})*(north-{$n}) ) as distance
				from
					os_gaz
				where
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en)
				order by distance asc,f_code+0,def_nam");
		} else {
			$places = $db->GetAll("select
					full_name,
					dsg,
					loc_placenames.reference_index,
					loc_adm1.name as adm1_name,
					loc_placenames.id as pid,
					power(e-{$e},2)+power(n-{$n},2) as distance
				from 
					loc_placenames
					left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and  loc_adm1.country = loc_placenames.country)
				where
					dsg LIKE 'PPL%' AND 
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en) AND
					loc_placenames.reference_index = {$reference_index}
				group by gns_ufi
				order by distance asc");


		}
	        if ($places && count($places))
			foreach ($places as $i => $place) {
        		        $places[$i]['full_name'] = _utf8_decode($place['full_name']);
        		}

		//fails quickly if not using memcached!
		$memcache->name_set('g',$mkey,$places,$memcache->compress,$memcache->period_long);
		
		return $places;
	}
	
	function findByNational($reference_index,$e,$n,$radius = 25005,$f_codes = null,$gazetteer = '') {
		global $CONF,$memcache;
		
		if (empty($gazetteer)) {
			$gazetteer = $CONF['use_gazetteer'];
		} 
		
		$mkey = "$reference_index,$e,$n,$radius,$f_codes,$gazetteer";
		//fails quickly if not using memcached!
		$places =& $memcache->name_get('g',$mkey);
		if ($places)
			return $places;
		
		$db=&$this->_getDB();
		
		//to optimise the query, we scan a square centred on the
		//the required point
		$left=$e-$radius;
		$right=$e+$radius;
		$top=$n-$radius;
		$bottom=$n+$radius;

		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

			//this is actully slower: 0.029 vs 0.025 (how to do it without a Distance() function)
			//$point = "'POINT({$e} {$n})'";
			//$sql = 	ROUND(GLength(LineStringFromWKB(LineString(AsBinary(point_en),
			//				AsBinary(GeomFromText($point))  )))) as distance

		if ($gazetteer == 'OS250' && $reference_index == 1) {
			$places = array();
			$left=$e-$radius;
			$right=$e+$radius;
			$top=$n-$radius;
			$bottom=$n+$radius;

			$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

			$places = $db->GetRow("select
					`def_nam` as full_name,
					'PPL' as dsg,
					1 as reference_index,
					`full_county` as adm1_name,
					(seq + 2000000) as pid,
					( (east-{$e})*(east-{$e})+(north-{$n})*(north-{$n}) ) as distance,
					'OS250' as gaz
				from
					os_gaz_250
				where
					CONTAINS(
						GeomFromText($rectangle),
						point_en)
				order by distance asc limit 1");

			$placeradius = 5005;
			if (sqrt($places['distance']) > $placeradius) {
				//if nothing near try finding a feature
				
				$e = (floor($e/1000) * 1000) + 500;
				$n = (floor($n/1000) * 1000) + 500;
				
				$left=$e-$placeradius;
				$right=$e+$placeradius;
				$top=$n-$placeradius;
				$bottom=$n+$placeradius;
				
				if (is_array($f_codes) && count($f_codes)) {
					$codes = "'".implode("','",$f_codes)."'";
				} else {
					$codes = "'C','T','O'";
				}
				
				$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

				$places2 = $db->GetRow("select
						`def_nam` as full_name,
						'PPL' as dsg,
						1 as reference_index,
						`full_county` as adm1_name,
						`hcounty` as hist_county,
						(seq + 1000000) as pid,
						( (east-{$e})*(east-{$e})+(north-{$n})*(north-{$n}) ) as distance,
						f_code,
						'OS' as gaz
					from
						os_gaz
					where
						CONTAINS( 	
							GeomFromText($rectangle),
							point_en) AND
						f_code not in ($codes)
					order by distance asc,f_code+0 asc limit 1");
				if (count($places2) && sqrt($places2['distance']) < $placeradius) {
					$places = $places2;
					$places['full_name'] .= ' ['.$db->getOne("select code_name from os_gaz_code where f_code = '".$places['f_code']."'")."]";
				}
			}
		} elseif ($gazetteer == 'OS' && $reference_index == 1) {
			
			$e = (floor($e/1000) * 1000) + 500;
			$n = (floor($n/1000) * 1000) + 500;
		
			$places = array();
			if (!$f_codes) {
		//first try looking up a big city/town for 'in'
				$radius2 = 2005;
				$left=$e-$radius2;
				$right=$e+$radius2;
				$top=$n-$radius2;
				$bottom=$n+$radius2;

				$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

				$places = $db->GetRow("select
						`def_nam` as full_name,
						'PPL' as dsg,
						1 as reference_index,
						`full_county` as adm1_name,
						`hcounty` as hist_county,
						(seq + 1000000) as pid,
						( (east-{$e})*(east-{$e})+(north-{$n})*(north-{$n}) ) as distance,
						1 as isin,
						'OS' as gaz
					from
						os_gaz
					where
						CONTAINS( 	
							GeomFromText($rectangle),
							point_en) AND
						f_code in ('C','T')
						order by f_code+0 asc,distance asc limit 1");
			}
			if (count($places) == 0) {
				$left=$e-$radius;
				$right=$e+$radius;
				$top=$n-$radius;
				$bottom=$n+$radius;
				
				$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";
				
				if (is_array($f_codes) && count($f_codes)) {
					$codes = "'".implode("','",$f_codes)."'";
				} else {
					$codes = "'C','T','O'";
				}
		//otherwise lookup a nearby settlement
				$places = $db->GetRow("select
						`def_nam` as full_name,
						'PPL' as dsg,
						1 as reference_index,
						`full_county` as adm1_name,
						`hcounty` as hist_county,
						(seq + 1000000) as pid,
						( (east-{$e})*(east-{$e})+(north-{$n})*(north-{$n}) ) as distance,
						'OS' as gaz
					from
						os_gaz
					where
						CONTAINS( 	
							GeomFromText($rectangle),
							point_en) AND
						f_code in ($codes)
					order by distance asc,f_code+0 asc limit 1");

				$placeradius = 4005;
				if (sqrt($places['distance']) > $placeradius) {
		//if nothing near try finding a feature
		
					//can reduce the size of the search

					$left=$e-$placeradius;
					$right=$e+$placeradius;
					$top=$n-$placeradius;
					$bottom=$n+$placeradius;

					$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

					$places2 = $db->GetRow("select
							`def_nam` as full_name,
							'PPL' as dsg,
							1 as reference_index,
							`full_county` as adm1_name,
							`hcounty` as hist_county,
							(seq + 1000000) as pid,
							( (east-{$e})*(east-{$e})+(north-{$n})*(north-{$n}) ) as distance,
							f_code,
							'OS' as gaz
						from
							os_gaz
						where
							CONTAINS( 	
								GeomFromText($rectangle),
								point_en) AND
							f_code not in ($codes)
						order by distance asc,f_code+0 asc limit 1");
					if (count($places2) && sqrt($places2['distance']) < $placeradius) {
						$places = $places2;
						$places['full_name'] .= ' ['.$db->getOne("select code_name from os_gaz_code where f_code = '".$places['f_code']."'")."]";
					}
				}
			}
		} else if ($gazetteer == 'hist' && $reference_index == 1) {
			$places = $db->GetRow("select
					full_name,
					'PPL' as dsg,
					1 as reference_index,
					`acounty` as adm1_name,
					`hcounty` as hist_county,
					(gaz_id + 800000) as pid,
					( (e-{$e})*(e-{$e})+(n-{$n})*(n-{$n}) ) as distance,
					'hist' as gaz
				from
					loc_abgaz
				where
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en)
				order by distance asc limit 1");
		} else if ($gazetteer == 'towns' && $reference_index == 1) {
			$places = $db->GetRow("select
					name as full_name,
					'PPL' as dsg,
					reference_index,
					'' as adm1_name,
					(id + 900000) as pid,
					power(e-{$e},2)+power(n-{$n},2) as distance,
					'towns' as gaz
				from 
					loc_towns
				where
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en) AND
					reference_index = {$reference_index}
				order by distance asc limit 1");
		} else {
	//lookup a nearby settlement
			$places = $db->GetRow("select
					full_name,
					dsg,
					loc_placenames.reference_index,
					loc_adm1.name as adm1_name,
					loc_placenames.id as pid,
					power(e-{$e},2)+power(n-{$n},2) as distance,
					'geonames' as gaz
				from 
					loc_placenames
					left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and  loc_adm1.country = loc_placenames.country)
				where
					dsg LIKE 'PPL%' AND 
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en) AND
					loc_placenames.reference_index = {$reference_index}
				order by distance asc limit 1");

	//if found very close then lookup mutliple
			$d = 2500*2500;	
			if (isset($places['distance']) && $places['distance'] < $d) {
				$nearest = $db->GetAll("select
					distinct full_name,
					loc_placenames.id as pid,
					power(e-{$e},2)+power(n-{$n},2) as distance,
					'geonames' as gaz
				from 
					loc_placenames
					left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and  loc_adm1.country = loc_placenames.country)
				where
					dsg LIKE 'PPL%' AND 
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en) AND
					loc_placenames.reference_index = {$reference_index} and
					power(e-{$e},2)+power(n-{$n},2) < $d
				group by gns_ufi
				order by distance asc limit 5");
				$values = array();
				foreach ($nearest as $id => $value) {
					$values[] = $value['full_name'];
				}
				$places['full_name'] = implode(', ',$values);
				$places['full_name'] = preg_replace('/\,([^\,]+)$/',' and $1',$places['full_name']);
			}
		}
		if (isset($places['distance']))
			$places['distance'] = round(sqrt($places['distance'])/1000)+0.01;
		$places['reference_name'] = $CONF['references'][$places['reference_index']];
		
		//fails quickly if not using memcached!
		$memcache->name_set('g',$mkey,$places,$memcache->compress,$memcache->period_long);
		
		return $places;
	}


	function findPlacename($placename) {
		global $places; //only way to get the array into the compare functions
		global $USER;
		global $CONF,$memcache;
		
		$mkey = strtolower(trim($placename)).'.v5';//need to invalidate the whole cache. 
		//fails quickly if not using memcached!
		$places =& $memcache->name_get('g',$mkey);
		if ($places)
			return $places;
		
		$db = $this->_getDB();

		$ismore = 0;
		$placename = str_replace('?','',$placename,$ismore);
		$places = array();
		
		if (is_numeric($placename)) {
			if ($placename > 1000000) {
				$places = $db->GetAll("select `def_nam` as full_name,'PPL' as dsg,`east` as e,`north` as n,1 as reference_index,`full_county` as adm1_name from os_gaz where seq=".$db->Quote($placename-1000000));
			} else {
				$places = $db->GetAll("select full_name,dsg,e,n,loc_placenames.reference_index,loc_adm1.name as adm1_name from loc_placenames left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and  loc_adm1.country = loc_placenames.country) where id=".$db->Quote($placename));
			}
		} elseif (!$ismore) {
			list($placename,$county) = preg_split('/\s*,\s*/',$placename);
			
			if (!empty($county)) {
				$qcount = $db->Quote($county);
				
				$places = $db->GetAll("select `def_nam` as full_name,'PPL' as dsg,`east` as e,`north` as n,1 as reference_index,`full_county` as adm1_name,code_name as dsg_name,(seq + 1000000) as id,km_ref as gridref from os_gaz inner join os_gaz_code using (f_code) where def_nam=".$db->Quote($placename)." and (full_county = $qcount OR hcounty = $qcount)");
			} else {
				$qplacename = $db->Quote($placename);
				$sql_where  = "def_nam=$qplacename";
				$sql_where2  = "full_name=$qplacename";
				if (strpos($placename,' ') !== FALSE) {
					$county = $db->getOne("select `name` from os_gaz_county where $qplacename LIKE CONCAT('%',name)");
					if (!empty($county)) {
						$qcount = $db->Quote($county);
					
						$placename = preg_replace("/\s+$county/i",'',$placename);
						$qplacename = $db->Quote($placename);
						
						$sql_where .= " or (def_nam=$qplacename and full_county = $qcount)";
						$sql_where2 .= " or full_name=$qplacename"; //we cant search easily on county here!
					}
				} 
				//todo need to 'union'  with other gazetterr! (as if one match in each then will no work!) 
				$places = $db->GetAll("select `def_nam` as full_name,'PPL' as dsg,`east` as e,`north` as n,1 as reference_index,`full_county` as adm1_name,code_name as dsg_name,(seq + 1000000) as id,km_ref as gridref from os_gaz inner join os_gaz_code using (f_code) where $sql_where");
				if (count($places) == 0) {
					$places = $db->GetAll("select full_name,dsg,e,n,reference_index,id,loc_dsg.name as dsg_name from loc_placenames inner join loc_dsg on (loc_placenames.dsg = loc_dsg.code) where full_name=$qplacename");
					
					if ($c = count($places)) {
						require_once('geograph/conversions.class.php');
						$conv = new Conversions;
						foreach($places as $id => $row) {
							if (empty($row['gridref'])) {
								list($places[$id]['gridref'],) = $conv->national_to_gridref($row['e'],$row['n'],4,$row['reference_index']);
							}
							$places[$id]['full_name'] = _utf8_decode($row['full_name']);
						}
					}
				}
			}
		}
		
		if (count($places) == 1) {
			#we done!
		} else {
			$limit = (strlen($placename) > 3)?60:20;
			$limi2 = 40;
			if ($USER->registered) {
				$limit *= 2;
				$limi2 *= 2;
			}
			
			//starts with (both gaz's)
			$places = $db->GetAll($sql = "
			(select
				(seq + 1000000) as id,
				`def_nam` as full_name,
				'PPL' as dsg,`east` as e,`north` as n,
				code_name as dsg_name,
				1 as reference_index,
				`full_county` as adm1_name,
				`hcounty` as hist_county,
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
				'' as hist_county,
				'' as gridref
			from 
				loc_placenames
				inner join loc_dsg on (loc_placenames.dsg = loc_dsg.code) 
				left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and  loc_adm1.country = loc_placenames.country)
			where
				dsg LIKE 'PPL%' AND loc_placenames.reference_index != 1 AND
				full_name LIKE ".$db->Quote($placename.'%')."
			group by gns_ufi
			LIMIT $limit)");
			if (isset($_GET['debug']))
				print "<pre>$sql</pre>count = ".count($places)."<hr>";
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
					`hcounty` as hist_county,
					km_ref as gridref
				from 
					os_gaz
					inner join os_gaz_code using (f_code)
				where
					os_gaz.f_code IN ('C','T','O') AND
					def_nam_soundex = SOUNDEX(".$db->Quote($placename).") AND
					def_nam NOT LIKE ".$db->Quote($placename.'%')."
				limit $limi2"));
				if (isset($_GET['debug']))
					print "<pre>$sql</pre>count = ".count($places)."<hr>";
			}
			
			if (count($places) < 10 || $ismore) {
				//contains (OS)
				$places = array_merge($places,$db->GetAll($sql = "
				select
					(seq + 1000000) as id,
					`def_nam` as full_name,
					'PPL' as dsg,`east` as e,`north` as n,
					code_name as dsg_name,
					1 as reference_index,
					`full_county` as adm1_name,
					`hcounty` as hist_county,
					km_ref as gridref
				from 
					os_gaz
					inner join os_gaz_code using (f_code)
				where
					os_gaz.f_code IN ('C','T','O') AND
					`def_nam` LIKE ".$db->Quote('%'.$placename.'%')." AND
					`def_nam` NOT LIKE ".$db->Quote($placename.'%')."
				limit $limi2"));
				if (isset($_GET['debug']))
					print "$limi2<pre>$sql</pre>count = ".count($places)."<hr>";
			}
			
			if (count($places) < 10 || $ismore) {
				//search the widest possible
				$places2 = $db->GetAll($sql = "
				(select
					(seq + 1000000) as id,
					`def_nam` as full_name,
					'PPL' as dsg,`east` as e,`north` as n,
					code_name as dsg_name,
					1 as reference_index,
					`full_county` as adm1_name,
					`hcounty` as hist_county,
					km_ref as gridref
				from 
					os_gaz
					inner join os_gaz_code using (f_code)
				where
					os_gaz.f_code NOT IN ('C','T','O') AND
					( `def_nam` LIKE ".$db->Quote('%'.$placename.'%')."
					OR def_nam_soundex = SOUNDEX(".$db->Quote($placename).") )
				order by 
					def_nam = ".$db->Quote($placename)." desc,
					def_nam_soundex = SOUNDEX(".$db->Quote($placename).") desc
				limit $limi2) UNION
				(select 
					id, 
					full_name,
					dsg,e,n,
					loc_dsg.name as dsg_name,
					loc_placenames.reference_index,
					loc_adm1.name as adm1_name,
					'' as hist_county,
					'' as gridref
				from 
					loc_placenames
					inner join loc_dsg on (loc_placenames.dsg = loc_dsg.code) 
					left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and  loc_adm1.country = loc_placenames.country)
				where
					full_name LIKE ".$db->Quote('%'.$placename.'%')."
					OR full_name_soundex = SOUNDEX(".$db->Quote($placename).")
				group by gns_ufi
				order by 
					full_name = ".$db->Quote($placename)." desc,
					full_name_soundex = SOUNDEX(".$db->Quote($placename).") desc
				LIMIT $limi2)");
				if (isset($_GET['debug']))
					print "<pre>$sql</pre>count2 = ".count($places2)."<hr>";
				if (count($places2)) {
					if (count($places)) {
						foreach ($places2 as $i2 => $place2) {
							$found = 0; $look = str_replace("-",' ',$place2['full_name']);
							foreach ($places as $i => $place) {
								if ($place['full_name'] == $look && $place['reference_index'] == $place2['reference_index'] && 
										($d = pow($place['e']-$place2['e'],2)+pow($place['n']-$place2['n'],2)) && 
										($d < 5000*5000) ) {
									$found = 1; break;
								}
							}
							if (!$found) 
								array_push($places,$place2);
						}
					} else {
						$places =& $place2;
					}
				}
			}
			if ($c = count($places)) {
				require_once('geograph/conversions.class.php');
				$conv = new Conversions;
				foreach($places as $id => $row) {
					if (empty($row['gridref'])) {
						list($places[$id]['gridref'],) = $conv->national_to_gridref($row['e'],$row['n'],4,$row['reference_index']);
					}
			                $places[$id]['full_name'] = _utf8_decode($row['full_name']);
				}
				if ($c > 4) {
					$placename = strtolower($placename);
					foreach($places as $id => $row) {
						$p1 = strtolower($row['full_name']);
						if (strpos($p1,$placename) === FALSE && levenshtein(strtolower($p1),$placename) > strlen($row['full_name'])/2) {
							unset($places[$id]);
						}
					}
				}
			}
		}
		
		//fails quickly if not using memcached!
		$memcache->name_set('g',$mkey,$places,$memcache->compress,$memcache->period_long);
		
		return $places;
	}


	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection(!empty($GLOBALS['DSN2'])?$GLOBALS['DSN2']:$GLOBALS['DSN']);
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
