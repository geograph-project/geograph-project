<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

$db=NewADOConnection($GLOBALS['DSN']);

if (empty($_GET['key']) || preg_match("/[^\w\.@]/",$_GET['key']))
	die("ERROR: no api key or email address");
	
$sql = "SELECT * FROM `apikeys` WHERE `apikey` = '{$_GET['key']}' AND (`ip` = INET_ATON('{$_SERVER['REMOTE_ADDR']}') OR `ip` = 0) AND `enabled` = 'Y'";

$profile = $db->GetRow($sql);

if ($profile['apikey']) {
	$sql_hardlimit = $hardlimit = '';
} else {
	#die("ERROR: invalid api key. contact support at geograph dot co dot uk");
	$hardlimit = 250;
	$sql_hardlimit = " LIMIT $hardlimit";
} 

# let the browser know what's coming
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"geograph.csv\"");




$sql_crit = '';

echo "Id,Name,Grid Ref,Submitter,Image Class";
if (isset($_GET['thumb'])) {
	require_once('geograph/gridimage.class.php');
	$gridimage = new GridImage;
	$sql_from = ',gi.user_id,x,y';
	echo ",Thumb URL";
}
if (isset($_GET['ll'])) {
	$sql_from = ',wgs84_lat,wgs84_long';
	echo ",Lat,Long";
} elseif (isset($_GET['en'])) {
	echo ",Easting,Northing";
}

if (isset($_GET['taken'])) {
	echo ",Date Taken";
	$sql_from .= ",imagetaken";
}
if (isset($_GET['ppos'])) {
	echo ",Photographer Eastings, Photographer Northings";
	$sql_from .= ",viewpoint_eastings,viewpoint_northings";
}
if (isset($_GET['dir'])) {
	echo ",View Direction";
	$sql_from .= ",view_direction";
}
echo "\n";

if (isset($_GET['ri']) && preg_match("/^\d$/",$_GET['ri']) ) {
	$sql_crit .= " AND reference_index = {$_GET['ri']}";
}

if (isset($_GET['since']) && preg_match("/^\d+-\d+-\d+$/",$_GET['since']) ) {
	$sql_crit .= " AND upd_timestamp >= '{$_GET['since']}' $sql_hardlimit";
} elseif (isset($_GET['last']) && preg_match("/^\d+ \w+$/",$_GET['last']) ) {
	$_GET['last'] = preg_replace("/s$/",'',$_GET['last']);
	$sql_crit .= " AND upd_timestamp > date_sub(now(), interval {$_GET['last']}) $sql_hardlimit";
} elseif (isset($_GET['limit']) && preg_match("/^\d+(,\d+|)?$/",$_GET['limit'])) {
	if ($hardlimit) {
		if (preg_match("/^(\d+),(\d+)?$/",$_GET['limit'],$m)) {
			$_GET['limit'] = "{$m[1]},$hardlimit";
		} else {
			$_GET['limit'] = min($_GET['limit'],$hardlimit);
		}
	}
	$sql_crit .= " ORDER BY upd_timestamp DESC LIMIT {$_GET['limit']}";
} elseif (empty($_GET['i'])) {
	die("ERROR: whole db export disabled. contact support at geograph dot co dot uk");
	$sql_crit .= " $sql_hardlimit";
}

if (isset($_GET['supp'])) {
	$mod_sql = "moderation_status in ('accepted','geograph')";
} else {
	$mod_sql = "moderation_status = 'geograph'";

}

$i=(!empty($_GET['i']))?intval($_GET['i']):'';

if ($i) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	
		$pg = (!empty($_GET['page']))?intval($_GET['page']):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}

	$engine = new SearchEngine($i);
	
	if (isset($_GET['count'])) {
		if (preg_match("/^\d+$/",$_GET['count'])) {
			$engine->criteria->resultsperpage = $_GET['count'];
		} elseif ($_GET['count'] == -1) {
			$engine->criteria->resultsperpage = 999999999;
		}
		if ($hardlimit) {
			$engine->criteria->resultsperpage = min($engine->criteria->resultsperpage,$hardlimit);
		}
	}
	
	//return a recordset
		//if want en then we HAVE to use the non cached version!
	$recordSet = $engine->ReturnRecordset($pg,isset($_GET['en']));

} elseif (isset($_GET['en'])) {
	if (isset($_GET['ftf'])) {
		$mod_sql .= " and ftf = 1"; 
	}
	$recordSet = &$db->Execute("select gridimage_id,title,grid_reference,realname,imageclass,nateastings,natnorthings,gi.user_id $sql_from 
	from user 
	inner join gridimage gi using(user_id) 
	inner join gridsquare using(gridsquare_id) 
	where $mod_sql $sql_crit");
} else {
	if (isset($_GET['supp'])) {
		$mod_sql = 1; //no point checking what will always be 1 ;-)
	}
	if (isset($_GET['ftf'])) {
		$mod_sql .= " and ftf = 1"; 
	}
	$recordSet = &$db->Execute("select gridimage_id,title,grid_reference,realname,imageclass,user_id $sql_from 
	from gridimage_search gi 
	where $mod_sql $sql_crit");
}
$counter = -1;
while (!$recordSet->EOF) 
{
	$image = $recordSet->fields;
	if (strpos($image['title'],',') !== FALSE || strpos($image['title'],'"') !== FALSE) {
		$image['title'] = '"'.str_replace('"', '""', $image['title']).'"';
	}
	if (strpos($image['imageclass'],',') !== FALSE || strpos($image['imageclass'],'"') !== FALSE) {
		$image['imageclass'] = '"'.str_replace('"', '""', $image['imageclass']).'"';
	}
	echo "{$image['gridimage_id']},{$image['title']},{$image['grid_reference']},{$image['realname']},{$image['imageclass']}";
	if (isset($_GET['thumb'])) {
		$gridimage->fastInit($image);
		echo ','.$gridimage->getThumbnail(120,120,true);
	}
	if (isset($_GET['en'])) {
		if ($image['nateastings'])
			echo ",{$image['nateastings']},{$image['natnorthings']}";
	} elseif (isset($_GET['ll'])) {
		echo ",{$image['wgs84_lat']},{$image['wgs84_long']}";
	}
	if (isset($_GET['taken'])) {
		echo ",{$image['imagetaken']}";
	}
	if (isset($_GET['ppos'])) {
		echo ",{$image['viewpoint_eastings']},{$image['viewpoint_northings']}";
	}
	if (isset($_GET['dir'])) {
		echo ",{$image['view_direction']}";
	}

	echo "\n";
	$recordSet->MoveNext();
	$counter++;
}
$recordSet->Close();
	
//todo
//if (isset($_GET['since']) && preg_match("/^\d+-\d+-\d+$/",$_GET['since']) ) {
// or if (isset($_GET['last']) && preg_match("/^\d+ \w+$/",$_GET['last']) ) {
// ... find all rejected (at first glance think only need ones submitted BEFORE but moderated AFTER, as ones submitted after wont be included!) - either way shouldnt harm to include them anyway!
	
$sql = "UPDATE apikeys SET accesses=accesses+1, records=records+$counter,last_use = NOW() WHERE `apikey` = '{$_GET['key']}'";

$db->Execute($sql);	

?>
