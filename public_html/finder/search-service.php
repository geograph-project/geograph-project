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
if (!empty($_GET['refresh'])) {
	init_session();
}


#customGZipHandlerStart();

$smarty = new GeographPage;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$q = isset($_GET['q'])?$_GET['q']:'';

$q = preg_replace('/ OR /',' | ',$q);

                        //remove any colons in tags - will mess up field: syntax
                $q = preg_replace('/\[([^\]]+)[:]([^\]]+)\]/','[$1~~~$2]',$q);


$q = preg_replace('/(-?)\b([a-z_]+):/','@$2 $1',$q);

                        //seperate out tags!
                if (preg_match_all('/(-?)\[([^\]]+)\]/',$q,$m)) {
                        $q2 = '';
                        foreach ($m[2] as $idx => $value) {
                                $q = str_replace($m[0][$idx],'',$q);
                                 $value = strtr($value,':-','  ');
                                 if (strpos($value,'~~~') > 0) {
                                         $bits = explode('~~~',$value,2);
                                         $q2 .= " ".$m[1][$idx].'"__TAG__ '.implode(' __PRE__ ',$bits).' __TAG__"';
                                 } else
                                         $q2 .= " ".$m[1][$idx].'"__TAG__ '.$value.' __TAG__"';
                        }
                        if (!empty($q2)) {
                                $q .= " @tags".$q2;
                        }
                }

$q = trim(preg_replace('/[^\w~\|\(\)@"\/\*=<^$,-]+/',' ',trim(strtolower($q))));

$q = preg_replace('/(\w+)(-\w+[-\w]*\w)/e','"\\"".str_replace("-"," ","$1$2")."\\""',$q);

$q = preg_replace('/^(.*) *near +([a-zA-Z]{1,2} *\d{2,5} *\d{2,5}) *$/','$2 $1',$q);
//todo - handle full placenames with near, by looking up in gaz :)

if (empty($q)) {
	die('no query');
}

$searchmode = (isset($_GET['mode']) && preg_match('/^\w+$/' , $_GET['mode']))?$_GET['mode']:'';

$template = "search_service.tpl";
$cacheid = md5($q.'|'.$searchmode).(isset($_GET['inner'])+1).(isset($_GET['feedback'])+1);

if (!empty($_GET['before']) && preg_match('/^\d{4}(-\d{2})*$/',$_GET['before'])) {
	$cacheid .= "b".$_GET['before'];
} elseif (!empty($_GET['after']) && preg_match('/^\d{4}(-\d{2})*$/',$_GET['after'])) {
	$cacheid .= "a".$_GET['after'];
}
if (!empty($_GET['function']) && preg_match('/^\w+$/',$_GET['function'])) {
        $cacheid .= "ff".$_GET['function'];
}



customCacheControl(filemtime(__FILE__),$cacheid,false);
customExpiresHeader(3600*6,true,true);


if (!$smarty->is_cached($template, $cacheid))
{

#location
	if (preg_match('/^([a-zA-Z]{1,2}) +(\d{1,5})(\.\d*|) +(\d{1,5})(\.*\d*|)/',$q,$matches) && $matches[1] != 'tp') {
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($matches[0],true);

		if ($grid_ok) {
			$gr = $square->grid_reference;
			$e = $square->nateastings;
			$n = $square->natnorthings;
			$q = preg_replace("/^{$matches[0]}\s*/",'',$q);
		} else {
			$r = "\t--invalid Grid Ref--";
			$nocache = 1;
		}

	} else if (preg_match('/^([a-zA-Z]{1,2})(\d{2,10})\b/',$q,$matches) && $matches[1] != 'tp') {

		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($matches[0],true);

		if ($grid_ok) {
			$gr = $square->grid_reference;
			$e = $square->nateastings;
			$n = $square->natnorthings;
			$q = preg_replace("/^{$matches[0]}\s*/",'',$q);
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
		$cl = GeographSphinxConnection('client', !empty($_GET['new']));

		$mode = SPH_MATCH_ALL;
		if (strpos($q,'~') === 0) {
			$q = preg_replace('/^\~/','',$q);
			if (substr_count($q,' ') > 0) //at least one word
				$mode = SPH_MATCH_ANY;
		} elseif (preg_match('/[~\|\(\)@"\/-]/',$q)) {
			$mode = SPH_MATCH_EXTENDED;
		}
		$index = "gi_stemmed,gi_stemmed_delta";

//if (preg_match('/crossgrid/i',$q) && (!preg_match('/supplemental/i',$q) || preg_match('/geograph|-supplemental/',$q)) ) {
//	die("unable to execute query");
//}

		if ($searchmode) {
			switch ($searchmode) {
				case '1': //default ranking mode
					#SPH_RANK_PROXIMITY_BM25
					$cl->SetMatchMode(SPH_MATCH_ALL);
					break;
				case '6': //any mode
					$cl->SetMatchMode(SPH_MATCH_ANY);
					break;
				case '7': //phrase mode
					$cl->SetMatchMode(SPH_MATCH_PHRASE);
					break;
				case '2': //custom
				case '5': //custom+wordcount
					$words = preg_split('/\s+/',$q);
					$quorum = max(1,count($words) - 2);
					$ordered = implode(' << ',$words);

					$cl->SetMatchMode(SPH_MATCH_EXTENDED);
					$q = "\"^$q\$\" | \"^$q\" | \"$q\" | ($ordered) | ($q) | \"$q\"/$quorum";

					if ($searchmode == 5) {
						$cl->SetRankingMode(SPH_RANK_WORDCOUNT);
					}
					break;
				case '8': //try just one mode
					$cl->SetMatchMode(SPH_MATCH_EXTENDED);
					$cl->SetRankingMode(SPH_RANK_BM25);
					break;
				case '9': //try just one mode
					$cl->SetMatchMode(SPH_MATCH_EXTENDED);
					$cl->SetRankingMode(SPH_RANK_PROXIMITY);
					break;
				case '10': //very simple
					$cl->SetMatchMode(SPH_MATCH_EXTENDED);
					$cl->SetRankingMode(SPH_RANK_WORDCOUNT);
					break;
				case '11': //wildcard!
					$cl->SetRankingMode(SPH_RANK_NONE);
					break;
				case '12': //just latest
					$cl->SetSortMode ( SPH_SORT_EXTENDED, "@id DESC" );
					$cl->SetMatchMode(SPH_MATCH_EXTENDED);
					$cl->SetRankingMode(SPH_RANK_NONE); //we dont need any ranking...
					break;
				case '13': 
					$cl->SetMatchMode(SPH_MATCH_EXTENDED);
					$cl->SetRankingMode(SPH_RANK_SPH04);
					break;
				case '4': //try some field weights
					$cl->SetIndexWeights(array('title'=>100,'comment'=>30));
					break;
				case '3'://find some popular squares...
					$sphinx = new sphinxwrapper($q);
					$sphinx->pageSize = 40;
					$sphinx->processQuery();

					$ids = $sphinx->returnIds(1,'sqim');

					if (empty($ids)) {
						die("unable to identify images");
					}
					$id_str = implode(',',$ids);

					$db = GeographDatabaseConnection(true);
					$grs = $db->GetCol("select grid_reference from gridsquare where gridsquare_id in ($id_str)");

					$gr_str = implode('|',$grs);

					$words = preg_split('/\s+/',$q);
					$quorum = max(1,count($words) - 1);

					$q = "\"$q\"/$quorum @grid_reference ($gr_str)";

					$cl->SetMatchMode(SPH_MATCH_EXTENDED);
					$cl->SetRankingMode(SPH_RANK_WORDCOUNT);
					break;
			}
			$cl->SetLimits($offset,10);
			$smarty->assign("mode",$searchmode);
		} else {
			if (strpos($q,'*') !== FALSE) {
				$index = 'gi_star';
			}
			$cl->SetIndexWeights(array('title'=>100));
			$cl->SetSortMode ( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
			$cl->SetMatchMode ( $mode );
			$cl->SetLimits($offset,25);
		}

if (!empty($_GET['function'])) {
	$bits = explode('_',$_GET['function']);

	switch($bits[1]) {
		case 'user': $attribute = 'auser_id'; break;
		case 'class': $attribute = 'classcrc'; break;
		case 'day': $attribute = 'takendays'; break;
		case 'year': $attribute = 'atakenyear'; break;
		case 'myriad': $attribute = 'amyriad'; break;
		case 'hectad': $attribute = 'ahectad'; break;
		case 'gridref': $attribute = 'agridsquare'; break;
		case 'centi': $attribute = 'scenti'; break;
		case 'combi': $attribute = 'auser_id'; break; //TODO!
	}

	if ($bits[0] == 'first') {
		$cl->setSelect("withinfirstx($attribute,2) as myint,$attribute as group");
		$cl->setFilter('myint',array(1));
	} elseif ($bits[0] == 'serial') {
		$cl->setSelect("uniqueserial($attribute) as sequence");
		$cl->SetSortMode(SPH_SORT_EXTENDED, "sequence ASC, @relevance DESC, @id DESC" );
	}
}



		if (!empty($_GET['before']) && preg_match('/^\d{4}(-\d{2})*/',$_GET['before'])) {
			while (strlen($_GET['before'])<10) {
				$_GET['before'] .= "-01";
			}
			$crit = new SearchCriteria();
			$days = $crit->toDays($_GET['before']);

			$cl->SetFilterRange('takendays',1,$days);

		} elseif (!empty($_GET['after']) && preg_match('/^\d{4}(-\d{2})*/',$_GET['after'])) {
			while (strlen($_GET['after'])<10) {
				$_GET['after'] .= "-01";
			}
			$crit = new SearchCriteria();
			$days = $crit->toDays($_GET['after']);
			$now = $crit->toDays('NOW()');

			$cl->SetFilterRange('takendays',$days,$now);
		}

		$q = preg_replace('/@text\b/','@(title,comment,imageclass)',$q);
		$q = preg_replace('/@notshared\b/','@!(snippet,snippet_title,snippet_id)',$q);
		$q = preg_replace('/@shared\b/','@(snippet,snippet_title,snippet_id)',$q);
		$q = preg_replace('/@not(\w+)\b/','@!($1)',$q);

		$res = $cl->Query ( $q, $CONF['sphinx_prefix'].$index );

if (!empty($_GET['debug'])) {
	print_r($cl);
	print_r($q);
	print_r($CONF['sphinx_prefix'].$index);
	print_r($res);
}

		if (strlen($q) < 64 && $mode != SPH_MATCH_EXTENDED && !isset($_GET['inner']) && !isset($_GET['feedback']))
			$smarty->assign("suggestions",didYouMean($q,$cl));
		if (isset($_GET['inner'])) {
			 $smarty->assign("inner",1);
		}
		if (isset($_GET['feedback'])) {
			 $smarty->assign("feedback",1);
			 $smarty->assign("q",$qo);
		}
		// --------------

		if ( $res===false )
		{
			print "\tQuery failed: -- please try again later.\n".$cl->getLastError();
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
		if ($searchmode) {
			$db = GeographDatabaseConnection(false);
			$ins = "INSERT INTO search_ranking_results SET
				mode = ".intval(@$searchmode).",
				q = ".$db->Quote(@$qo).",
				res_crc = CRC32(".$db->Quote(@$where)."),
				total_found = ".intval(@$res['total_found']);
			$db->Execute($ins);
		} else {
			$db = GeographDatabaseConnection(true);
		}

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
	global $CONF;
	$cl->SetMatchMode ( SPH_MATCH_ANY );
	$res = $cl->Query ( preg_replace('/\s*\b(the|to|of)\b\s*/',' ',$q), $CONF['sphinx_prefix'].'gaz' );
	$arr = array();
	if ( $res!==false && is_array($res["matches"]) )
	{
		if ( $cl->GetLastWarning() )
			print "\nWARNING: " . $cl->GetLastWarning() . "\n\n";

		$query_info = "Query '$qo' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";

		$db=GeographDatabaseConnection(true);

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

