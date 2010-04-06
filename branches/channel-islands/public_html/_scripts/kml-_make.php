<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
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

if (!isLocalIPAddress())
{
	init_session();
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

set_time_limit(3600*24);


#####################


print "Drop<br>";flush();

$db->Execute("drop table if exists gridimage_kml");


#####################


print "Create<br>";flush();

#need to join gi and g2 as each contains columns not in the other. 

$db->Execute("
create table gridimage_kml
ENGINE = MYISAM
select 
	gi.gridimage_id,
	gi.x,
	gi.y,
	gi.grid_reference,
	gi.title,
	gi.title2,
	gi.credit_realname,
	gi.realname,
	gi.user_id,
	gi.wgs84_lat,
	gi.wgs84_long,
	g2.natgrlen,
	g2.view_direction,
	gi.point_xy,
	0 as `tile`,
	gs.imagecount
from 
	gridimage_search gi
	inner join gridsquare gs 
		using (grid_reference)
	inner join gridimage g2 
		on (gi.gridimage_id = g2.gridimage_id)
order by 
	imagecount desc,
	(natgrlen != '4') desc,
	(gi.moderation_status = 'geograph') desc,
	rand(date(now()))");

# order preference:
#  dense squares first (so they shown in the tile selection)
#  higher percision first (so the 'first' image is less likly to be grid based.) 
#  geograph first (so will show geograph first) 
#  then random so all images should get a a lookin

#####################


print "Index<br>";flush();

$db->Execute("ALTER IGNORE TABLE `gridimage_kml` ADD UNIQUE (`x` ,`y`) ");

/* done by the unique index above! 
print "Select<br>";flush();
$db->Execute("
delete from gridimage_kml 
where gridimage_id <> ANY 
	(select gridimage_id from gridimage_kml group by x,y)");
*/


#####################


print "Temp<br>";flush();

//need to create temp table as wont allow join withself in update (even in subquery) 
$db->Execute("
create TEMPORARY table gridimage_kml2 ENGINE=HEAP
select gridimage_id from gridimage_kml group by x div 3,y div 3
");
$db->Execute("ALTER IGNORE TABLE `gridimage_kml2` ADD UNIQUE (gridimage_id) ");


#####################


print "Tile<br>";flush();

$db->Execute("
update gridimage_kml k,gridimage_kml2 k2 set k.tile = 1 
where k.gridimage_id = k2.gridimage_id");


#####################


print "Spatial Index<br>";flush();

$db->Execute("ALTER TABLE `gridimage_kml` ADD SPATIAL KEY(point_xy)");


#####################

print "Start Rendering!<br>";flush();

$db->Execute("update kmlcache set `rendered` = 0 where `level` = 1");



print "END";
exit;

?>
