<?php
/**
 * $Project: GeoGraph $
 * $Id: geotrip_submit.php 7816 2013-03-31 00:17:09Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Rudi Winter (http://www.geograph.org.uk/profile/2520)
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

$USER->mustHavePerm("moderator");



$db = GeographDatabaseConnection(false);
// can now use mysql_query($sql); directly, or mysql_query($sql,$db->_connectionID);





$smarty->assign('page_title', 'Geo-Trip Moderation :: Geo-Trips');


$smarty->display('_std_begin.tpl','trip_admin');

print "<h2><a href=\"/geotrips/\">Geo-Trips</a> :: Moderation</h2>";

if (!empty($_POST['delete'])) {
	$db->Execute("DELETE FROM geotrips WHERE id = ".intval($_POST['delete']));
	$db->Execute("DELETE FROM content WHERE source = 'trip' AND foreign_id = ".intval($_POST['delete']));
	print "<p>trip #".intval($_POST['delete'])." deleted</p>";
}

print "<p>NOTE: This page is just a stop-gap until we develop a full moderation section. Use it to delete any spam</p>";


print "<table cellspacing=0 cellpadding=2 border=1>";
print "<tr>";
print "<th>ID</th>";
print "<th>Title</th>";
print "<th>By</th>";
print "<th>Action</th>";
print "</tr>";

$limit = 100;
if (!empty($_GET['all']))
	$limit = 10000;

$data = $db->getAll("SELECT id,title,start,location,uid,user,FROM_UNIXTIME(updated) as updated FROM geotrips ORDER BY id DESC LIMIT $limit");
foreach ($data as $row) {
	print "<tr>";
	print "<td>#".htmlentities($row['id'])."</td>";
	print "<td><a href=\"/geotrips/geotrip_show.php?trip={$row['id']}\">".htmlentities($row['title']?$row['title']:'untitled')."</a><br/><span style=\"font-size:0.7em\">".htmlentities($row['location'])." from ".htmlentities($row['start'])."</td>";
	print "<td><a href=\"/profile/{$row['uid']}\">".htmlentities($row['user'])."</a></td>";
	print "<td><a href=\"/geotrips/geotrip_edit.php?trip={$row['id']}\">Edit</a> ";
	print "<form method=post style=\"display:inline\" onclick=\"return confirm('Are you sure you wish to delete #{$row['id']} - ".htmlentities($row['title']?$row['title']:'untitled')." - by ".htmlentities($row['user'])."? THIS CAN NOT BE UNDONE. THERE IS NO BACKUP.')\">";
	print "<input type=hidden name=delete value=".htmlentities($row['id']).">";
	print "<input type=submit value=\"Delete\"></form></td>";
	print "</tr>";

}
print "</table>";

if (empty($_GET['all']))
	print "<p>$limit most recent shown. <a href=\"?all=1\">Show all</a></p>";

$smarty->display('_std_end.tpl');

