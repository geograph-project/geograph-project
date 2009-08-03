<?php
/**
 * $Project: GeoGraph $
 * $Id: export.csv.php 5247 2009-02-18 19:43:29Z barry $
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

  $smarty = new GeographPage;
  dieUnderHighLoad();

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

$db=NewADOConnection($GLOBALS['DSN']);

if ((empty($_GET['key']) || preg_match("/[^\w\.@]/",$_GET['key'])) )
	die("ERROR: no api key ");
	
$sql = "SELECT * FROM `apikeys` WHERE `apikey` = ".$db->Quote($_GET['key'])." AND (`ip` = INET_ATON('{$_SERVER['REMOTE_ADDR']}') OR `ip` = 0) AND `enabled` = 'Y'";

$profile = $db->GetRow($sql);

if ($profile['apikey']) {
} else {
	die("ERROR: invalid api key.");
} 

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

# let the browser know what's coming
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"user_stat.csv\"");


if (!empty($_GET['users']) && preg_match('/^[\d,]+$/',$_GET['users'])) {
	$where = " where user_id IN (".implode(',',array_unique(explode(',',$_GET['users']))).")";

} else {
	$where = '';
}

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$columns = array();
//there must be a nicer way of doing this?
foreach ($db->getRow("SELECT * FROM user_stat WHERE user_id = 0") as $name => $value) {
	if (!preg_match('/_(rise|rank)$/',$name)) {
		$columns[] = $name;
	}
}
$columns = implode(',',$columns);

$recordSet = &$db->Execute("SELECT $columns FROM user_stat $where");

$counter = -1;
while (!$recordSet->EOF) 
{
	$row = $recordSet->fields;
	
	if ($counter == -1) {
		print implode(',',array_keys($row))."\n";
	}

	print implode(',',array_values($row))."\n";
	
	$recordSet->MoveNext();
	$counter++;
}
$recordSet->Close();

?>
