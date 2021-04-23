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

$smarty = new GeographPage;

$template='stuff_broken_links.tpl';

$_GET['hide'] = 'on';

if (isset($_GET['mine']) && $USER->hasPerm("basic")) {
	$_GET['u'] = $USER->user_id;
	$_GET['missing'] = 'on';
}

$u = (isset($_GET['u']) && is_numeric($_GET['u']) && $_GET['u'] == $USER->user_id)?intval($_GET['u']):0;

$l = (isset($_GET['l']) && is_numeric($_GET['l']))?intval($_GET['l']):3;

$cacheid=md5(serialize($_GET));

if (!empty($_POST) && !empty($_POST['retry'])) {

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');

	$a = array_map(array($db,'Quote'),array_unique($_POST['retry']));

	$sql = "UPDATE gridimage_link SET next_check = NOW() WHERE url IN (".implode(",",$a).")";

	$db->Execute($sql);

	$smarty->clear_cache($template, $cacheid);
}




if (!$smarty->is_cached($template, $cacheid))
{
	if (empty($db)) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	}

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$tables = $andwhere = "";


	$smarty->assign('levels', array(
	'2' => 'Critical',
	'3' => 'Major',
	'9' => '404/410 Not Found',
	'10' => 'Soft-404 (suspect broken)',
	'4' => 'Possible Errors',
	'7' => 'Redirect Loop',
	'8' => 'Redirect to Error',
	'5' => 'Any Error',
	'' => '----------',
	'1' => 'Only OKs' ,
	'6' => 'Redirect but OK',
	 ));

	switch ($l) {
		case '1': //all ok
			$andwhere = ' AND HTTP_Status IN (200,304)'; break;
		case '2': //any content/server error
			$andwhere = ' AND (HTTP_Status BETWEEN 400 and 699)'; break;
		case '3': //some iffy
			$andwhere = ' AND (HTTP_Status IN (600,601,602) OR HTTP_Status BETWEEN 400 and 499)'; break;
		case '4': //more questionable
			$andwhere = ' AND HTTP_Status BETWEEN 300 and 699 AND HTTP_Status != 304'; break;
		case '5': //ANY error
			$andwhere = ' AND HTTP_Status NOT IN (200,304) AND HTTP_Status_final != 200'; break;
		case '6': //Redirect OK
			$andwhere = ' AND HTTP_Status IN (301,302,307) AND HTTP_Status_final = 200'; break;
		case '7': //Redirect Loop
			$andwhere = ' AND HTTP_Status IN (301,302,307) AND HTTP_Status_final IN (301,302,307)'; break;
		case '8': //Redirect to Error
			$andwhere = ' AND HTTP_Status IN (301,302,307) AND HTTP_Status_final >=400'; break;
		case '9': //just 404s (even via redirect)
			$andwhere = ' AND HTTP_Status_final IN (404,410)'; break;
		case '10': //suspect Urls
			/* $andwhere = " AND (HTTP_Status=200 AND page_title = SUBSTRING_INDEX(SUBSTRING_INDEX(url,'/',3),'/',-1)) OR
					(HTTP_Status=200 AND page_title = REPLACE('www.','',SUBSTRING_INDEX(SUBSTRING_INDEX(url,'/',3),'/',-1))) OR
                                        (HTTP_Status=200 AND page_title like '%Not Found%') OR
					(HTTP_Status in (301,302) AND HTTP_Location RLIKE '[[:<:]](404|error)[[:>:]]' AND HTTP_Status_final = 200)"; */
			$andwhere = "soft_ratio>0.8"; break;
	}

	if ($u) {
		$andwhere .= " AND user_id = $u";
		$tables = " inner join gridimage using (gridimage_id) ";
	}
	if (isset($_GET['missing'])) {
		$andwhere .= " AND archive_url = '' AND archive_checked NOT LIKE '000%'";
		$smarty->assign('missing_checked', ' checked');
	}
        if (isset($_GET['hide'])) {
                $andwhere .= " AND last_found > upd_timestamp";
                $tables = " inner join gridimage using (gridimage_id) ";
                $smarty->assign('hide_checked', ' checked');
        }

	if (!empty($_GET['group'])) {
		if ($_GET['group'] == 2) {
			$group = "substring_index(url,'/',3),HTTP_Status_final";
		} else {
			$group = "url,HTTP_Status_final";
		}

	$sql = "SELECT
	l.*, count(*) as count
	FROM gridimage_link l $tables
	WHERE HTTP_Status != 0 AND next_check > NOW() AND next_check < '9999-00-00' AND parent_link_id = 0 $andwhere
	GROUP BY $group
	ORDER BY last_checked desc,HTTP_Location
	LIMIT 100";

		$smarty->assign('grouped', intval($_GET['group']));
	} else {
	$sql = "SELECT
	l.*
	FROM gridimage_link l $tables
	WHERE HTTP_Status != 0 AND next_check > NOW() AND next_check < '9999-00-00' AND parent_link_id = 0 $andwhere
	ORDER BY last_checked desc,HTTP_Location
	LIMIT 100";
	}

	$table = $db->getAll($sql);

	$smarty->assign_by_ref('table', $table);
	$smarty->assign("total",count($table));

	$smarty->assign("l",$l);
	$smarty->assign("u",$u);

	$smarty->assign('codes', array(
	200 => "OK",
	206 => "Partial Content",
	300 => "Multiple Choices",
	301 => "Moved Permanently",
	302 => "Found",
	304 => "Not Modified",
	307 => "Temporary Redirect",
	400 => "Bad Request",
	401 => "Unauthorized",
	403 => "Forbidden",
	404 => "Not found",
	405 => "Method Not Allowed",
	406 => "Not Acceptable",
	410 => "Gone (Forever)",
	411 => "Length Required",
	412 => "Precondition Failed",
	500 => "Server error",
	503 => "Out of resources",
	501 => "Not Implemented",
	502 => "Bad Gateway",
	506 => "Variant Also Varies",
	600 => "Unable to contact Server",
	601 => "Non HTTP?" ));
} else {
	$smarty->assign("u",$u);
}


$smarty->display($template, $cacheid);


