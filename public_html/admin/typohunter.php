<?php
/**
 * $Project: GeoGraph $
 * $Id: typohunter.php 9036 2019-10-12 16:36:56Z barry $
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
require_once('geograph/imagelist.class.php');
init_session();

if (!empty($CONF['db_read_connect2'])) {
        //concoct a special writable connection to SECOND slave!
        $DSN_READ = $CONF['db_read_driver'].'://'.
                $CONF['db_user'].':'.$CONF['db_pwd'].
                '@'.$CONF['db_read_connect2'].
                '/'.$CONF['db_db'].$CONF['db_read_persist'];

	dieUnderHighLoad(2.2);
} else {
	dieUnderHighLoad(0.8);
}

$smarty = new GeographPage;

if (!empty($_COOKIE['MapSrv']) && $_COOKIE['MapSrv'] == "OSOS") {
        //temp as page doesnt work on https (mainly maps!)
        pageMustBeHTTP();
} else {
        pageMustBeHTTPS();
}

customGZipHandlerStart();


$USER->mustHavePerm("basic");


$template='admin_typohunter.tpl';

$max_gridimage_id = 0;
$count = 0;
if (!empty($_GET['next'])) {
	$token=new Token;

	if ($token->parse($_GET['next']) && $token->hasValue("id")) {
		$max_gridimage_id = intval($token->getValue("id"));
		$count = intval($token->getValue("c"));
	} else {
		die("invalid token");
	}
}
$include = $exclude = $profile = '';
if (!empty($_GET['include'])) {
	$include= $_GET['include'];
}
if (!empty($_GET['exclude'])) {
	$exclude= $_GET['exclude'];
}

if (!empty($_GET['profile'])) {
	$profile= $_GET['profile'];
}

$size = (!empty($_GET['size']))?intval($_GET['size']):10000;
$size = max(100,min(50000,$size));

/*
$cacheid = md5("$include|$exclude|$profile|$size");
if (!empty($_GET['save']))
	$cacheid .= ".save";

if (!empty($_GET['over']))
	$cacheid .= ".over";

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600; //1hour cache
	customExpiresHeader(3600,false,true);
}*/
$cacheid = 'dynamic';

$smarty->assign('sizes',array(1000=>1000,3000=>3000,5000=>5000,10000=>10000,50000=>50000));
$smarty->assign('size',$size);

if (!empty($_GET['deep'])) {
	$template = "admin_typohunter_deep.tpl";

	$smarty->assign('include',$_GET['include']);
	$smarty->assign('exclude',$_GET['exclude']);
	$smarty->assign('profile',$_GET['profile']);

        $imagelist=new ImageList;
        $db = $imagelist->_getDB(true);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$lastid = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
	$smarty->assign('shards', intval($lastid/50000));

	$smarty->display($template, $cacheid);
	exit;
}



//regenerate?
if (//!$smarty->is_cached($template, $cacheid) && -- we now use 'dynamic'
	strlen($include)) {

	$smarty->assign('profile',$_GET['profile']);

	$imagelist=new ImageList;

	$db = $imagelist->_getDB(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$last_id = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

	######################################################
	//keywords profile

	if ($profile == 'keywords') {

		$pgsize = 50;

		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}

		$q = '@(title,comment,imageclass) ';
		if (!empty($_GET['include'])) {
			$q .= trim($_GET['include']);
			$smarty->assign('include',$_GET['include']);
		}
		if (!empty($_GET['exclude'])) {
			$q .= " -(".trim($_GET['exclude']).")";
			$smarty->assign('exclude',$_GET['exclude']);
		}

		if ($imagelist->getImagesBySphinx($q,$pgsize,$pg = 1)) {
			$total_results = $imagelist->resultCount;
		}

	######################################################
	//phrase profile

	} else {
		$where = array();

		if ($profile == 'expression') {
			if (!empty($_GET['include'])) {
				$where[] = '(title REGEXP BINARY '.$db->Quote($_GET['include']).
					' OR comment REGEXP BINARY '.$db->Quote($_GET['include']).')';
				$smarty->assign('include',$_GET['include']);
			}
			if (!empty($_GET['exclude'])) {
				$where[] = 'title NOT REGEXP BINARY '.$db->Quote($_GET['exclude']);
				$where[] = 'comment NOT REGEXP BINARY '.$db->Quote($_GET['exclude']);
				$smarty->assign('exclude',$_GET['exclude']);
			}
		} else {
			if (!empty($_GET['include'])) {
				$where[] = '(title LIKE '.$db->Quote('%'.$_GET['include'].'%').
					' OR comment LIKE '.$db->Quote('%'.$_GET['include'].'%').')';
				$smarty->assign('include',$_GET['include']);
			}
			if (!empty($_GET['exclude'])) {
				$where[] = 'title NOT LIKE '.$db->Quote('%'.$_GET['exclude'].'%');
				$where[] = 'comment NOT LIKE '.$db->Quote('%'.$_GET['exclude'].'%');
				$smarty->assign('exclude',$_GET['exclude']);
			}
		}

		if (isset($_GET['shard'])) {
		        $start = $_GET['shard']*50000;
		        $end = $start+49999;
			$where[] = "gridimage_id BETWEEN $start AND $end";

		} elseif (count($where)) {
			$where[] = 'gridimage_id > '.($last_id-$size);

		} else {
			die("umm?");
		}

		$where= implode(' AND ',$where);

		if (!empty($_GET['count'])) {
			customExpiresHeader(3600,false,true);

			$data = $db->getRow("SELECT gridimage_id, COUNT(*) AS matches FROM gridimage_search WHERE $where");
			outputJSON($data);
			exit;
		}

		$sql="select gridimage_id,user_id,realname,title,comment,grid_reference ".
			"from gridimage_search ".
			"where $where ".
			($max_gridimage_id?" and gridimage_id < $max_gridimage_id ":'').
			"order by gridimage_id desc limit 50";

if ($USER->user_id == 3)
	print "<hr>".htmlentities($sql)."<hr>";

		$imagelist->_getImagesBySql($sql);

		if (count($imagelist->images)) {
			$total_results = count($imagelist->images);
		}
	}

	######################################################
	//display results

	if (count($imagelist->images)) {

		if ($profile == 'expression') {
			$regex = '/^.*?(.{0,20})('.str_replace('/','\\/',$_GET['include']).')(.{0,20}).*?$/s';
		} elseif ($profile == 'keywords') {
			//remove =exact and "phrase char" - very basic, but proabbly deals with most (excepting stemming!)
			//todo, maybe use BuildExcerpts like Watchlist does!
			$regex = '/^.*?(.{0,20})('.preg_quote(preg_replace('/[="]/','',$_GET['include']),'/').')(.{0,20}).*?$/si';
		} else {
			$regex = '/^.*?(.{0,20})('.preg_quote($_GET['include'],'/').')(.{0,20}).*?$/si';
		}
		$replace = '... $1<b style=background-color:yellow;>$2</b>$3 ...';

		foreach ($imagelist->images as $i => $image) {
			if (preg_match($regex, $image->title))
				$imagelist->images[$i]->title_html = preg_replace($regex,$replace, $image->title);

			if (!empty($image->comment) && preg_match($regex, $image->comment))
				$imagelist->images[$i]->comment_html = preg_replace($regex,$replace, $image->comment);
		}


		$smarty->assign_by_ref('images', $imagelist->images);
		$smarty->assign_by_ref('image_count', $total_results);

		/*
		$last = $imagelist->images[count($imagelist->images)-1];

		$max_gridimage_id = $last->gridimage_id;
		$count++;

		if ($count < 10) {
			$token=new Token;
			$token->setValue("id", intval($max_gridimage_id));
			$token->setValue("c", intval($count));

			$smarty->assign('next', $token->getToken());
		}*/

	}

	######################################################
	//save results

	if ($db->readonly) {
		$db = GeographDatabaseConnection(false);
	}

		$where = array();
		$where[] = "include = ".$db->Quote($_GET['include']);
		$where[] = "exclude = ".$db->Quote($_GET['exclude']);
		$where[] = "profile = ".$db->Quote($_GET['profile']);

	//lookup see if already a saved typo (to prevent trying to save again!
	if ($typo_id = $db->getOne($sql = "SELECT typo_id FROM typo WHERE ".implode(' AND ',$where)." AND enabled=1")) {
		$smarty->assign('typo_id', $typo_id);
	}

		@$total_results+=0; //make sure a number
		$updates = array();
		$updates[] = "last_results = ".$total_results;
		$updates[] = "last_time=NOW()";
		$updates[] = "last_size=$size";
		$updates[] = "last_gridimage_id=$last_id";
		$updates[] = "total_results = total_results + ".$total_results;
		$updates[] = "total_runs = total_runs + 1";
		$updates[] = "last_user_id = ".$USER->user_id;

	//overwrite a differnt search
	if (!empty($_GET['over']) && !empty($_GET['old_id'])) {
		//we actully changing the values
		foreach ($where as $value)
			$updates[] = $value;
		$updates[] = 'enabled=1'; //so will renable, if previousll deleted!

                $db->Execute('UPDATE typo SET '.implode(',',$updates).' WHERE typo_id = '.$db->Quote($_GET['old_id']));

		$typo_id = intval($_GET['old_id']); //to make sure it shows as saved.
		$smarty->assign('typo_id', $typo_id);

	//save as a new item. (but will do update if duplicate!)
	} elseif (!empty($_GET['save'])) {
		$updates[] = 'enabled=1'; //so will renable, if previousll deleted!

		$inserts = $where;
		$inserts[] = "created=NOW()";
		$inserts[] = "user_id = ".$USER->user_id;

		$inserts[] = "last_results = ".$total_results;
		$inserts[] = "last_time=NOW()";
		$inserts[] = "last_size=$size";
		$inserts[] = "last_gridimage_id=$last_id";
		$inserts[] = "total_results = ".$total_results;
		$inserts[] = "total_runs = 1";
		$inserts[] = "last_user_id = ".$USER->user_id;

		$db->Execute('INSERT INTO typo SET '.implode(',',$inserts).' ON DUPLICATE KEY UPDATE '.implode(',',$updates));
		$smarty->assign('typo_id', $db->Insert_ID());

	//just update (if any!), no insert.
	} else {
                $db->Execute('UPDATE typo SET '.implode(',',$updates).' WHERE '.implode(' AND ',$where));
	}

	######################################################
	//load the old version

	if (!empty($_GET['old_id'])) {
		$smarty->assign('old_id', intval($_GET['old_id']));
		$row = $db->getRow("SELECT include,exclude FROM typo WHERE typo_id = ".$db->Quote($_GET['old_id']));
		$title = $row['include'];
		if (!empty($row['exclude']))
			$title .= "-({$row['exclude']})";
		$smarty->assign('old_title', $title);
	}
}


$smarty->display($template, $cacheid);

