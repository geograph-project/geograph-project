<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

if (isset($_GET['limit']) && preg_match("/^\d+(,\d+|)?$/",$_GET['limit'])) {
	$limit = $_GET['limit'];
} else {
	print "please specify a limit";
	exit;
}


if ($_GET['all']) {
	$where = "1";
} elseif ($_GET['geo']) {
	$where = "moderation_status = 'geograph'";
} else {
	$where = "moderation_status = 'geograph' and ftf = 1";
}


$recordSet = &$db->Execute("select gridimage_id	from gridimage_search where $where order by gridimage_id limit $limit");
$count=0;
while (!$recordSet->EOF) 
{
	$image = $recordSet->fields;

	print $image['gridimage_id']." ";
	file_get_contents("http://geourl.org/ping/?p=http://".$_SERVER['HTTP_HOST']."/photo/".$image['gridimage_id']);
	sleep(1);
	flush();
	$recordSet->MoveNext();
}
	

	
?> Finished.
