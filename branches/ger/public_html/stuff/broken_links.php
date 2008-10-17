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

if (isset($_GET['mine']) && $USER->hasPerm("basic")) {
	$_GET['u'] = $USER->user_id;
}

$u = (isset($_GET['u']) && is_numeric($_GET['u']) && $_GET['u'] == $USER->user_id)?intval($_GET['u']):0;

$l = (isset($_GET['l']) && is_numeric($_GET['l']))?intval($_GET['l']):3;


$cacheid=$u.'.'.$l;

if (!empty($_POST) && !empty($_POST['retry'])) {
	
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	}
	
	$a = array_map(array($db,'Quote'),array_unique($_POST['retry']));
	
	
	$sql = "UPDATE gridimage_link SET next_check = NOW() WHERE url IN (".implode(",",$a).")";
	
	$db->Execute($sql);
	
	$smarty->clear_cache($template, $cacheid);
}




if (!$smarty->is_cached($template, $cacheid))
{
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');
	}

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$tables = $andwhere = "";
	switch ($l) {
		case '1': //all ok
			$andwhere = ' AND HTTP_Status = 200'; break;
		case '2': //only critical
			$andwhere = ' AND (HTTP_Status IN (600,601,602) OR (HTTP_Status BETWEEN 400 and 499 AND HTTP_Status != 404))'; break;
		case '3': //some iffy
			$andwhere = ' AND (HTTP_Status IN (600,601,602) OR (HTTP_Status BETWEEN 400 and 599))'; break;
		case '4': //more questionable
			$andwhere = ' AND (HTTP_Status IN (600,601,602) OR (HTTP_Status BETWEEN 300 and 599))'; break;
		case '5': //ANY error
			$andwhere = ' AND HTTP_Status != 200'; break;
	}

	if ($u) {
		$andwhere .= " AND user_id = $u";
		$tables = " inner join gridimage using (gridimage_id) ";
	}

	$sql = "SELECT 
	l.*
	FROM gridimage_link l $tables
	WHERE HTTP_Status != 0 AND next_check > NOW() $andwhere
	ORDER BY last_checked desc,parent_link_id,HTTP_Location
	LIMIT 100";

	$table = $db->getAll($sql);

	$smarty->assign_by_ref('table', $table);
	$smarty->assign("total",count($table));

	$smarty->assign('levels', array(
	'1' => 'Only OKs' ,
	'2' => 'Critical',
	'3' => 'Major',
	'4' => 'Possible Errors',
	'5' => 'Any Error' ));
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
	404 => "Not found <small>- may be temporary</small>",
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

?>
