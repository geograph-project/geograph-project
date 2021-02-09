<?php
/**
 * $Project: GeoGraph $
 * $Id: glossary.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();


customNoCacheHeader(); //because we performing a user redirect.


if (empty($_GET) && $USER->user_id && $USER->registered) {
	$db = GeographDatabaseConnection(true);
	$cols = "id,searchdesc,searchclass,use_timestamp,favorite";
	$rows = $db->getAll("
	(SELECT $cols FROM queries WHERE user_id = {$USER->user_id} AND favorite = 'Y' and searchuse = 'search')
	UNION ALL
	(SELECT $cols FROM queries WHERE user_id = {$USER->user_id} AND favorite = 'N' and searchuse = 'search' ORDER BY id DESC LIMIT 100)
	order by use_timestamp desc,id desc
	");
	$smarty = new GeographPage;
	$smarty->display('_std_begin.tpl');
	print "<h2>Browser Redirect</h2>";
	print "<p>Note many searches will NOT transfer via this function, and even ones that do, may not be exact. But it should maintain most of the main filters (eg keywords, nearby location, and date filters). It should warn if using a function that knows is not compatible, but other searches may still simply not work in browser...";
	print "<hr>";
	print "<p>Recent Searches for testing purposes:</p>";

	print "<ul>";
	foreach ($rows as $row) {
		$style = ($row['favorite'] == 'Y')?'style=font-weight:bold':'';
		print "<li $style><a href=?i={$row['id']}>".htmlentities($row['searchdesc'])."</a>";
		print " {$row['searchclass']}";
	}
	print "</ul>";
	$smarty->display('_std_end.tpl');
	exit;
}

require_once('geograph/searchcriteria.class.php'); //doesnt work via autoload
require_once('geograph/searchengine.class.php');


$engine = new SearchEngine($_GET['i']);
$engine->criteria->getSQLParts();

$sphinx = $engine->criteria->sphinx;
$sql = $engine->criteria->sql;

$bits = array();
$explain = array();
$warning = array();
###########################

if ($engine->criteria->searchclass == 'Special') {

	//marked list ([where] => inner join gridimage_query using (gridimage_id) where query_id = 73210341)
	if (preg_match('/inner join gridimage_query.* query_id = (\d+)/',$sql['where'],$m)) {

		if (!empty($_GET['import'])) {
			$db = GeographDatabaseConnection(true);

			$ids = $db->getCol("SELECT gridimage_id FROM gridimage_query WHERE query_id = {$m[1]}");

			if (!empty($ids)) {
			        $str = '';
			        if (isset($_COOKIE['markedImages']) && !empty($_COOKIE['markedImages'])) {
		        	        $str = $_COOKIE['markedImages'];
		                	foreach ($ids as $id)
                		        	if (!preg_match('/\b'.$id.'\b/',$str))
		                                	$str .= ",$id";
			        } else {
        	        		$str = implode(',',$ids);
			        }

		        	//setcookie('markedImages', $str, time()+3600*24*10,'/');
			        //setcookie urlencodes the string, and setrawcookie discards cookie if contains comma!
			        //markedImages= ... ; expires=Mon, 26-Jun-2017 12:32:10 GMT; Max-Age=864000; path=/

			        $age = 3600*24*10;
		        	//Wdy, DD-Mon-YYYY HH:MM:SS GMT
			        $date = date('D, j-M-Y H:i:s', time()+$age)." GMT";

			        header("Set-Cookie: markedImages=$str; expires=$date; Max-Age=$age; path=/");
			}

			header("Location: /browser/#!/marked=1");
			exit;
		}
		$warning[] = "This Search can not be transfered at this time";
		$offer_import = true;

	} elseif (preg_match('/\blabel = \'(.+?)\'/',$sql['where'],$m)) {
		$bits['groups'] = "groups+%22".urlencode($m[1])."%22";
		$explain['groups'] = "Grouped by {$m[1]}";

	//inner join user_gridsquare ug on (gs.grid_reference = ug.grid_reference and ug.user_id = 60932)
	} elseif (preg_match('/and ug\.user_id = (\d+)\)/',$sql['where'],$m)) {
		$bits['my_square'] = "my_square=".intval($m[1]);
		$explain['my_square'] = "Squares visited by contributor #{$m[1]}";

	} else
		$warning[] = "This Search can not be transfered at this time";

} elseif (!empty($sphinx['impossible']) && empty($engine->criteria->limit9)) {
	$warning[] = "The results almost certainly wont match original search, but MAY be close";
}


if (!empty($sphinx['query'])) {

	if (trim($sphinx['query']) == trim($engine->criteria->searchtext)
		&& preg_match('/tagged \[(.*?)\]/',$engine->criteria->searchdesc,$m)) {
		//special case convert a single tag search, to a Filter!
		$bits['tags'] = "tags+%22".urlencode($m[1])."%22";
		$explain['tags'] = "Matching '{$m[1]}' in Tags";

	} else {
		if (preg_match('/snippet_id:(\d+)/',$sphinx['query'],$m)) {

			$db = GeographDatabaseConnection(true);
		        $id = intval($m[1]);
		        if ($row = $db->getRow("SELECT content_id,title FROM content WHERE foreign_id = $id AND source = 'snippet'")) {
	        	        $bits['content_title'] = "content_title=".urlencode($row['title']);
		                $bits['content_id'] = "content_id={$row['content_id']}";
				$explain['content_id'] = "In collection '{$row['title']}'";

				$sphinx['query'] = str_replace($m[0],'',$sphinx['query']);
		        } else {
        		        $warning[] = "The browser can't view images from this particular shared description";
		        }
		}
		$sphinx['query'] = str_replace('snippet_title:','snippets:',$sphinx['query']); //todo, COULD convert this to a filter, like done with tags above

		//todo in particular _SEP_/__TAG and the different named fields!

		$bits['q'] = "q=".urlencode(trim($sphinx['query']));
		$explain['q'] = "Keyword Match for '{$sphinx['query']}'";
		$warning[] = "The results of keyword search may not match original (the original search, and Browser have different keyword searching capablities)";
	}

}
if (!empty($sphinx['x'])) {
        require_once('geograph/conversions.class.php');
        $conv = new Conversions;

	if ($sphinx['d'] == 1) {
		//special case! d=1 means 'in' the square, rather than true radius search
		list($gr,$len) = $conv->internal_to_gridref($sphinx['x'],$sphinx['y'],4);
		$bits['gr'] = "grid_reference+%22".str_replace(' ','',$gr)."%22";
		$explain['gr'] = "In Square: $gr";

	} else {
	        list($gr,$len) = $conv->internal_to_gridref($sphinx['x'],$sphinx['y'],0);

		if ($engine->criteria->searchclass != 'GridRef' && !empty($engine->criteria->searchq)) {
			$bits['loc'] = "loc=".str_replace(' ','',$gr)."%20".urlencode($engine->criteria->searchq);
			$explain['gr'] = "Near: $gr, {$engine->criteria->searchq}";
		} else {
			$bits['loc'] = "loc=".str_replace(' ','',$gr);
			$explain['gr'] = "Near: $gr";
		}

		$bits['dist'] = "dist=".intval($sphinx['d']*1000);
		$explain['dist'] = "Within {$sphinx['d']}km";

		if ($len <= 2) {
			$warning[] = "The original search, would search center of 4fig gridsquare, the browser uses south west corner";
		}
	}
}
if (!empty($sphinx['bbox'])) {
	$warning[] = "Results are NOT filtered to the right Bounding Box";
}

//todo $sphinx['filters']
if (!empty($sphinx['filters'])) {
	foreach ($sphinx['filters'] as $filter => $value) {
		switch($filter) {
			case 'user_id':
				if (preg_match('/!(\d+)/',$value,$m)) {
					$bits['user'] = "user+-%22user".intval($m[1])."%22";
					$explain['user'] = "By User #{$m[1]}";
					break;
				} else {
					$bits['user'] = "user+%22user".intval($value)."%22";
					$explain['user'] = "NOT by User #{$value}";
					break;
				}
			case 'month': $bits['month'] = "takenmonth+%22".urlencode($value)."%22"; $explain['month'] = "Month: $value"; break;
			case 'myriad': case 'hectad':
			case 'status': case 'imageclass':
			case 'takenyear': case 'takenmonth': case 'takenday':
				$bits[$filter] = "$filter+%22".urlencode($value)."%22"; $explain[$filter] = "$filter: $value"; break;

			//submitted=1336172400,1337122799
			case 'submitted':
				$bits[$filter] = "submitted=".implode(',',$value); $explain[$filter] = "Submitted between ".implode(' and ',$value); break;

			//taken=2017-07-02,2017-07-03
			case 'takendays':
				$db = GeographDatabaseConnection(true);
				//todo, could perhaps just use limit7 directly?
				$from = $db->getOne("SELECT FROM_DAYS({$value[0]})");
				$to = $db->getOne("SELECT FROM_DAYS({$value[1]})");
				$bits[$filter] = "taken=$from,$to";
				$explain[$filter] = "Taken between ".implode(' and ',$value);
				break;

			//imageclass+%22Fountains%22
			case 'classcrc':
				if (!empty($engine->criteria->limit3)) {
					$bits['imageclass'] = "imageclass+%22".urlencode($engine->criteria->limit3)."%22";
					$explain['imageclass'] = "Category: {$engine->criteria->limit3}";
					break;
				}
/*
classcrc
scenti  /array
submitted arrya(timestamp range
takendays /array(days range
*/
			default:
				$warning[] = "Additional Filters [$filter] are not yet maintained";
		}
	}
}

if (!empty($engine->criteria->limit9)) {
	$db = GeographDatabaseConnection(true);
	$id = intval($engine->criteria->limit9);
	if ($row = $db->getRow("SELECT content_id,title FROM content WHERE foreign_id = $id AND source in ('themed','gallery') ")) {
	        $bits['content_title'] = "content_title=".urlencode($row['title']);
		$bits['content_id'] = "content_id={$row['content_id']}";
		$explain['content_id'] = "In collection '{$row['title']}'";

	} else {
		$warning[] = "The browser can't view images from this particular thread";
	}
}

if (!empty($engine->criteria->limit10)) {
	$warning[] = "The browser can't view images from a route";
}
if (!empty($engine->criteria->limit11)) {
	$warning[] = "The browser can't view images from category mapping";
}

if (!empty($engine->criteria->groupby) || !empty($engine->criteria->breakby)) {
	$warning[] = "The group/break settings are NOT maintained!";
}

if (!empty($sphinx['sort'])) {
	switch(trim($sphinx['sort'])) {
		case '@geodist ASC, @relevance DESC, @id DESC': break; //this works as default
		case '@relevance DESC, @id DESC': break; //this works as default
		case '@id ASC': $bits['sort'] = 'sort=submitted_up'; $explain['sort'] = "Submitted Oldest First"; break;
		case '@id DESC': $bits['sort'] = 'sort=submitted_down'; $explain['sort'] = "Submitted Recent First"; break;
		default:
			$warning[] = "The Sort order of the original search is NOT maintained!";
	}
}

###########################


if (!empty($warning) || !empty($_GET['debug'])) {
	$smarty = new GeographPage;
        $smarty->display('_std_begin.tpl');
	print "<h2>Browser Redirect</h2>";

	print "<h3>from: Search for images".htmlentities($engine->criteria->searchdesc)."</h3>";

	if (!empty($explain)) {
	        print "<h3>to:</h3><ul>";
                foreach ($explain as $text)
                        print "<li>".htmlentities($text)."</li>";
                print "</ul>";
	}

	if (!empty($_GET['debug'])) {
		print "<pre>";
		unset($engine->criteria->db);
		print_r($engine->criteria);
		print_r($warning);
		print_r($bits);
		print "</pre>";
	} else {
		print "<h3>Warnings</h3><ul>";
		foreach ($warning as $warn)
			print "<li>".htmlentities($warn)."</li>";
		print "</ul>";
	}

	print "<a href=\"/browser/#!/".implode("/",$bits)."\"><b>Continue to Browser</b> regardless</a>";

	if (!empty($offer_import)) {
		print "<p>However, can <a href=\"?i={$engine->query_id}&import=true\">Import all images from this saved search into your Current Marked list</a> (adding to any images already marked)<br>... your marked list will then be accessible in the browser</p>";

		if (!empty($_COOKIE['markedImages']) && $engine->criteria->user_id == $USER->user_id) {
			print "<p>Or can simply view you <a href=\"/browser/#!/marked=1\">current marked list in the Browser</a> (which ignores the images from this search!)</p>";
		}
	}


	if ($CONF['forums']) {
		print "<br><br><hr><a href=\"/discuss/index.php?&action=vthread&forum=12&topic=14812&page=16#11\">Please give feedback on this function in the forum</a><br><br>";
	}

	$smarty->display('_std_end.tpl');

} else {

	header("Location: /browser/#!/".implode("/",$bits));
}

//todo, update use_timestamp  ??
