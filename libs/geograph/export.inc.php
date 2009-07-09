<?
/**
 * $Project: GeoGraph $
 * $Id: rastermap.class.php 2876 2007-01-07 20:51:00Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007  Barry Hunter (geo@barryhunter.co.uk)
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
 
 
$db=NewADOConnection($GLOBALS['DSN']);

if ((empty($_GET['key']) || preg_match("/[^\w\.@]/",$_GET['key'])) && empty($_GET['u']))
	die("ERROR: no api key or email address");
	
$sql = "SELECT * FROM `apikeys` WHERE `apikey` = ".$db->Quote($_GET['key'])." AND (`ip` = INET_ATON('{$_SERVER['REMOTE_ADDR']}') OR `ip` = 0) AND `enabled` = 'Y'";

$profile = $db->GetRow($sql);

if ($profile['apikey']) {
	$hardlimit = 2500;
	$sql_hardlimit = " LIMIT $hardlimit";
} elseif (!empty($_GET['u']) && preg_match("/^\d+$/",$_GET['u']) && (init_session() || true) && $USER->hasPerm('basic')) {
	$sql_hardlimit = $hardlimit = '';
} else {
	#die("ERROR: invalid api key. contact support at geograph dot co dot uk");
	$hardlimit = 250;
	$sql_hardlimit = " LIMIT $hardlimit";
} 

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

$sql_from = $sql_crit = '';

$csvhead = "Id,Name,Grid Ref,Submitter,Image Class";
if (!empty($_GET['thumb'])) {
	require_once('geograph/gridimage.class.php');
	$gridimage = new GridImage;
	$csvhead .= ",Thumb URL";
	$sql_from = ',gi.user_id,x,y,reference_index';
}
if (!empty($_GET['gr'])) {
	$csvhead .= ",Subject";
	$sql_from = ',reference_index';
	if (!empty($_GET['ppos'])) {
		$csvhead .= ",Photographer";
		$sql_from .= ",viewpoint_eastings,viewpoint_northings,if(use6fig=1,6,viewpoint_grlen) as viewpoint_grlen";
	}
} elseif (!empty($_GET['en'])) {
	$csvhead .= ",Easting,Northing,Figures";
	if (isset($_GET['coords']) && empty($_GET['thumb'])) {
		$sql_from = ',x,y,reference_index';
	}
	if (!empty($_GET['ppos'])) {
		$csvhead .= ",Photographer Eastings,Photographer Northings,Photographer Figures";
		$sql_from .= ",viewpoint_eastings,viewpoint_northings,if(use6fig=1,6,viewpoint_grlen) as viewpoint_grlen";
	}
} elseif (!empty($_GET['ll'])) {
	$csvhead .= ",Lat,Long";
	$sql_from .= ',wgs84_lat,wgs84_long';
}

if (!empty($_GET['taken'])) {
	$csvhead .= ",Date Taken";
	$sql_from .= ",imagetaken";
}

if (!empty($_GET['dir'])) {
	$csvhead .= ",View Direction";
	$sql_from .= ",view_direction";
}

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

if (!empty($_GET['ri']) && preg_match("/^\d$/",$_GET['ri']) ) {
	$sql_crit .= " AND reference_index = {$_GET['ri']}";
}
$user_crit = 0;
if (!empty($_GET['u']) && preg_match("/^\d+$/",$_GET['u'])) {
	$sql_crit .= " AND gi.user_id = {$_GET['u']}";
	$user_crit = 1;
}

if (!empty($_GET['since']) && preg_match("/^\d+-\d+-\d+$/",$_GET['since']) ) {
	$sql_crit .= " AND upd_timestamp >= '{$_GET['since']}' $sql_hardlimit";
} elseif (!empty($_GET['last']) && preg_match("/^\d+ \w+$/",$_GET['last']) ) {
	$_GET['last'] = preg_replace("/s$/",'',$_GET['last']);
	$sql_crit .= " AND upd_timestamp > date_sub(now(), interval {$_GET['last']}) $sql_hardlimit";
} elseif (!empty($_GET['limit']) && preg_match("/^\d+(,\d+|)?$/",$_GET['limit'])) {
	if ($hardlimit) {
		if (preg_match("/^(\d+),(\d+)?$/",$_GET['limit'],$m)) {
			$_GET['limit'] = "{$m[1]},$hardlimit";
		} else {
			$_GET['limit'] = min($_GET['limit'],$hardlimit);
		}
	}
	$sql_crit .= " ORDER BY upd_timestamp DESC LIMIT {$_GET['limit']}";
} elseif (empty($_GET['i']) && empty($_GET['u'])) {
	die("ERROR: whole db export disabled. contact support at geograph dot co dot uk");
	$sql_crit .= " $sql_hardlimit";
}

if (!empty($_GET['supp']) xor empty($_GET['u'])) {
	$mod_sql = "moderation_status in ('accepted','geograph')";
} else {
	$mod_sql = "moderation_status = 'geograph'";
}

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#


$i=(!empty($_GET['i']))?intval($_GET['i']):'';

if ($i && !$user_crit ) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	
		$pg = (!empty($_GET['page']))?intval($_GET['page']):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}

	$engine = new SearchEngine($i);
	
	if (!empty($_GET['count'])) {
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

} elseif (!empty($_GET['en']) || !empty($_GET['ppos'])) {
	if (!empty($_GET['ftf'])) {
		$mod_sql .= " and ftf = 1"; 
	}
	$recordSet = &$db->Execute("select gridimage_id,title,grid_reference,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,imageclass,nateastings,natnorthings,if(use6fig=1,6,natgrlen) as natgrlen,gi.user_id $sql_from 
	from user 
	inner join gridimage gi using(user_id) 
	inner join gridsquare using(gridsquare_id) 
	where $mod_sql $sql_crit");
} else {
	if (!empty($_GET['supp'])) {
		$mod_sql = 1; //no point checking what will always be 1 ;-)
	}
	if (!empty($_GET['ftf'])) {
		$mod_sql .= " and ftf = 1"; 
	}
	$recordSet = &$db->Execute("select gridimage_id,title,grid_reference,credit_realname,realname,imageclass,user_id $sql_from 
	from gridimage_search gi 
	where $mod_sql $sql_crit");
}

?>
