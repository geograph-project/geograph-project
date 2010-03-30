<?php 
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');



#customGZipHandlerStart();

$smarty = new GeographPage;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$q = isset($_GET['q'])?$_GET['q']:'';

$q = preg_replace('/ OR /',' | ',$q);

$q = preg_replace('/(-?)\b([a-z_]+):/','@$2 $1',$q);

$q = trim(preg_replace('/[^\w~\|\(\)@"\/\*-]+/',' ',trim(strtolower($q))));

$q = preg_replace('/(\w+)(-\w+[-\w]*\w)/e','"\\"".str_replace("-"," ","$1$2")."\\""',$q);

$q = preg_replace('/^(.*) *near +([a-zA-Z]{1,3} *\d{2,5} *\d{2,5}) *$/','$2 $1',$q);
//todo - handle full placenames with near, by looking up in gaz :)

if (empty($q)) {
	die('no query');
}

$template = "search_service.tpl";
$cacheid = md5($q);

if (!$smarty->is_cached($template, $cacheid))
{

#location
	if (preg_match('/^([a-zA-Z]{1,3}) +(\d{1,5})(\.\d*|) +(\d{1,5})(\.*\d*|)/',$q,$matches) && $matches[1] != 'tp') {
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($matches[0],true);

		if ($grid_ok) {
			$gr = $square->grid_reference;
			$e = $square->nateastings;
			$n = $square->natnorthings;
			$q = preg_replace("/{$matches[0]}\s*/",'',$q);
		} else {
			$r = "\t--invalid Grid Ref--";
			$nocache = 1;
		}
		
	} else if (preg_match('/^([a-zA-Z]{1,3})(\d{2,10})\b/',$q,$matches) && $matches[1] != 'tp') {
	
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($matches[0],true);
					
		if ($grid_ok) {
			$gr = $square->grid_reference;
			$e = $square->nateastings;
			$n = $square->natnorthings;
			$q = preg_replace("/{$matches[0]}\s*/",'',$q);
		} else {
			$r = "\t--invalid Grid Ref--";
			$nocache = 1;
		}
	} 

	if (preg_match('/\bp(age|)(\d+)\s*$/',$q,$m)) {
		$offset = min(max((intval($m[2])-1)*25,0),984);
		$q = preg_replace('/\bp(age|)\d+\s*$/','',$q);
	} else {
		$offset = 0;
	}

	$qo = $q;
	if (strlen($qo) > 64) {
		$qo = '--complex query--';
	} 
	if ($r) {
		//Handle Error
		
	} elseif (!empty($e)) {
		//Location search
		
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		
		$e = floor($e/1000);
		$n = floor($n/1000);
		$grs = array();
		for($x=$e-2;$x<=$e+2;$x++) {
			for($y=$n-2;$y<=$n+2;$y++) {
				list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,4,$square->reference_index,false);
				$grs[] = $gr2;
				
			}
		}
		if (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~/','',$q);
			$q = "(".str_replace(" "," | ",$q).") (".join(" | ",$grs).")";
		} else {
			$q .= " (".join(" | ",$grs).")";
		}
		$qo .= " near $gr";
	} 
	
	if (1) {
		//text query
	
		
		// --------------
		require ( "3rdparty/sphinxapi.php" );
		
		$mode = SPH_MATCH_ALL;
		if (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~/','',$q);
			if (substr_count($q,' ') > 0) //at least one word
				$mode = SPH_MATCH_ANY;
		} elseif (preg_match('/[~\|\(\)@"\/-]/',$q)) {
			$mode = SPH_MATCH_EXTENDED;
		} 
		$index = "gi_stemmed,gi_delta_stemmed";
 if (strpos($q,'*') !== FALSE) {
	$index = 'gi_star';
}		
		$cl = new SphinxClient ();
		$cl->SetServer ( $CONF['sphinx_host'], $CONF['sphinx_port'] );
		$cl->SetWeights ( array ( 100, 1 ) );
		$cl->SetSortMode ( SPH_SORT_EXTENDED2, "@relevance DESC, @id DESC" );
		$cl->SetMatchMode ( $mode );
		$cl->SetLimits($offset,25);
		$res = $cl->Query ( $q, $index );
		
		if (strlen($q) < 64 && $mode != SPH_MATCH_EXTENDED)
			$smarty->assign("suggestions",didYouMean($q,$cl));
		
		// --------------
		
		if ( $res===false )
		{
			print "\tQuery failed: -- please try again later.\n";
			exit;
		} else
		{
			if ( $cl->GetLastWarning() )
				print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";
		
			$query_info = "Query '$qo' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
		}
		
		if (is_array($res["matches"]) ) {
		
			$ids = array_keys($res["matches"]);
			
			if (!empty($_GET['id'])) {
				header("Location: http://www.geograph.org.uk/search.php?marked=1&markedImages=".join(",",$ids));
				exit;
			}
			
			
			$where = "gridimage_id IN(".join(",",$ids).")";
		
			$sql = "SELECT gridimage_id,grid_reference,title,realname,user_id
			FROM gridimage_search
			WHERE $where
			LIMIT 60";
		} else {
			$r = "\t--none--";
		}
			
	}
	
	if ($sql) {
		$db=NewADOConnection($GLOBALS['DSN']);
		
		
		$result = mysql_query($sql) or die ("Couldn't select query : $sql " . mysql_error() . "\n");

		if (mysql_num_rows($result) > 0) {
			require_once('geograph/gridimage.class.php');
			
		
			$rows = array();
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				$rows[$row['gridimage_id']] = $row;
			}
			$images = array();
			foreach ($ids as $c => $id) {
				if ($row = $rows[$id]) {
					$gridimage = new GridImage;
					$gridimage->fastInit($rows[$id]);

					$images[] = $gridimage;
				}
			}
			$smarty->assign_by_ref("images",$images);
			$smarty->assign("query_info",$query_info);
		} else {
			$images = array();
			$smarty->assign_by_ref("images",$images);
			$smarty->assign("query_info","no results");
		}

	} else {
		$images = array();
		$smarty->assign_by_ref("images",$images);
		$smarty->assign("query_info","no results");
	}
	$smarty->assign("searchq",$qo);
}

$smarty->display($template,$cacheid);


function didYouMean($q,$cl) {
	$cl->SetMatchMode ( SPH_MATCH_ANY );
	$res = $cl->Query ( preg_replace('/\s*\b(the|to|of)\b\s*/',' ',$q), 'gaz' );
	$arr = array();
	if ( $res!==false && is_array($res["matches"]) )
	{
		if ( $cl->GetLastWarning() )
			print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

		$query_info = "Query '$qo' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";

		$db=NewADOConnection(!empty($GLOBALS['DSN2'])?$GLOBALS['DSN2']:$GLOBALS['DSN']);

		$ids = array_keys($res["matches"]);

		$where = "id IN(".join(",",$ids).")";

		$sql = "SELECT gr,name,localities
		FROM placename_index
		WHERE $where
		LIMIT 60";

		$result = mysql_query($sql) or die ("Couldn't select query : $sql " . mysql_error() . "\n");
		$r = '';
		if (mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
				foreach (preg_split('/[\/,\|]+/',trim(strtolower($row['name']))) as $word) {
					$word = preg_replace('/[^\w ]+/','',$word);
					if (strpos($q,$word) !== FALSE) {
						$row['query'] = str_replace($word,'',$q);
						$arr[] = $row;
					}

				}
			}
		}
	}
	return $arr;
}
	
exit;

?>
