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

$db = GeographDatabaseConnection(true);

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$andwhere = ($ri)?" and reference_index=$ri":'';	

$count = $db->cacheGetOne(86400,"SELECT COUNT(*) FROM gridsquare WHERE percent_land > 0 $andwhere");
	
$offset = rand(0,$count);

$gridref = $db->getOne("SELECT grid_reference FROM gridsquare WHERE percent_land > 0 $andwhere AND gridsquare_id > $offset"); //limit 1 added automatically

header("Location: /gridref/".$gridref);

?>