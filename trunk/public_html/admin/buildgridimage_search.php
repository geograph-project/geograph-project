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
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
$db2 = NewADOConnection($GLOBALS['DSN']);

require_once('geograph/conversions.class.php');
$conv = new Conversions;

//this takes a long time, so we output a header first of all
$smarty->display('_std_begin.tpl');

?>
<h2>gridimage_search Rebuild Tool</h2>
<form action="buildgridimage_search.php" method="post">
<input type="checkbox" id="recreate" name="recreate" value="1" checked="checked">
<label for="recreate">Recreate entire gridimage_search table from gridimage table</label><br>
&nbsp;<input type="checkbox" id="use_new" name="use_new" value="1" checked="checked">
<label for="use_new">Use multi-stage copy (recommended on a live site)</label><br>

<input type="checkbox" id="update" name="update" value="1" checked="checked">
<label for="update">Update lat/long values in gridimage_search</label><br>

<input type="submit" name="go" value="Start">
</form>

<?php

set_time_limit(3600*24);
	
if (isset($_POST['recreate']))
{
	echo "<h3>Rebuilding gridimage_search from gridimage</h3>";
	flush();
	
	if ($_POST['use_new']) {
	
		echo "<p>Creating gridimage copy...</p>";flush();
		$db->Execute("create table tmpimg select gridimage_id,moderation_status, title, submitted, imageclass, imagetaken, upd_timestamp,comment,ftf,seq_no,user_id,gridsquare_id from gridimage where moderation_status in ('accepted','geograph');");

		echo "<p>Creating gridsquare copy...</p>";flush();
		$db->Execute("create table tmpsq select gridsquare_id,grid_reference,x, y,reference_index,point_xy from gridsquare;");

		echo "<p>Creating user copy...</p>";flush();
		$db->Execute("create table tmpus select user_id,realname from user;");


		echo "<p>Add keys to copies...</p>";flush();
		$db->Execute("alter table tmpus add primary key(`user_id`);");
		$db->Execute("alter table tmpsq add primary key(`gridsquare_id`);");


		echo "<p>Clear out gridimage_search...</p>";flush();
		$db->Execute("truncate table gridimage_search;");

		$db->Execute("ALTER TABLE `gridimage_search` DISABLE KEYS;");

		echo "<p>Rebuilding gridimage_search...</p>";flush();
		$db->Execute("INSERT INTO gridimage_search
			SELECT gridimage_id, gi.user_id, moderation_status, title, submitted, imageclass, imagetaken, upd_timestamp, x, y, gs.grid_reference, user.realname,reference_index,comment,0,0,ftf,seq_no,point_xy,GeomFromText('POINT(0 0)')
			FROM tmpimg AS gi
			INNER JOIN tmpsq AS gs
			USING ( gridsquare_id )
			INNER JOIN tmpus as user ON ( gi.user_id = user.user_id );");

		echo "<p>Enable keys on gridimage_search...</p>";flush();
		$db->Execute("ALTER TABLE `gridimage_search` ENABLE KEYS;");

		echo "<p>Remove temp tables...</p>";flush();
		$db->Execute("drop table tmpimg");
		$db->Execute("drop table tmpsq");
		$db->Execute("drop table tmpus");
		
	
	} else {
	
	
		$db->Execute("TRUNCATE gridimage_search");


		$db->Execute("INSERT INTO gridimage_search
			SELECT gridimage_id, gi.user_id, moderation_status, title, submitted, imageclass, imagetaken, upd_timestamp, x, y, gs.grid_reference, user.realname,reference_index,comment,0,0,ftf,seq_no,point_xy,GeomFromText('POINT(0 0)')
			FROM gridimage AS gi
			INNER JOIN gridsquare AS gs
			USING ( gridsquare_id )
			INNER JOIN user ON ( gi.user_id = user.user_id )
			WHERE moderation_status in ('accepted','geograph') ");
	}
	
	echo "<h3>Rebuild completed</h3>";
	if (!isset($_POST['update']))
		print "<h3>It is now recommended to update the Lat/Long</h3>";

}

if (isset($_POST['update']))
{
	echo "<h3>Updating Lat/Long</h3>";
	flush();

	$start = time();

	$recordSet = &$db->Execute("select gridimage_id,x,y,reference_index,nateastings,natnorthings
		from gridimage
		INNER JOIN gridsquare AS gs USING ( gridsquare_id )
		where moderation_status in ('accepted','geograph')");
	$count=0;
	while (!$recordSet->EOF) 
	{
		$image = $recordSet->fields;
	
		if ($image['nateastings']) {
			list($lat,$long) = $conv->national_to_wgs84($image['nateastings'],$image['natnorthings'],$image['reference_index']);
		} else {
			list($lat,$long) = $conv->internal_to_wgs84($image['x'],$image['y'],$image['reference_index']);
		}
	
		$db2->Execute("UPDATE LOW_PRIORITY gridimage_search SET wgs84_lat = $lat, wgs84_long = $long,point_ll = GeomFromText('POINT($long $lat)') WHERE gridimage_id = ".$image['gridimage_id']);
		
		if (++$count%500==0) {
				printf("done %d at <b>%d</b> seconds<br/>",$count,time()-$start);
				flush();
		}
		$recordSet->MoveNext();
	}
	
	echo "<p>Lat/Long update complete</p>";
	
}


	$smarty->display('_std_end.tpl');
	exit;
	


	
?>
