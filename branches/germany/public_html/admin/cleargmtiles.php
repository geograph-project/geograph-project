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

#$db=NewADOConnection($GLOBALS['DSN']);
#if (!$db) die('Database connection failed');  

set_time_limit(3600*24);
#require_once('geograph/map.class.php');
#$map=new GeographMap;
#$map->rebuildGMcache();
$dir = $_SERVER['DOCUMENT_ROOT']."/maps";
#system("cd $dir && rm base/*/*/base_*_m.gd* detail/*/*/detail_*_m.png* label/*/*/label_*_m.gd* base/*/*/base_*_m.png* label/*/*/label_*_m.png*");
system("cd $dir && rm base/*/*/base_*_m.gd*");
system("cd $dir && rm detail/*/*/detail_*_m.png");
system("cd $dir && rm detail/*/*/detail_*_m.png*");
system("cd $dir && rm label/*/*/label_*_m.gd*");
system("cd $dir && rm base/*/*/base_*_m.png*");
system("cd $dir && rm label/*/*/label_*_m.png*");
#system("cd $dir && rm base/*/*/base_*_m.gd* detail/*/*/detail_*_m.png");
# rm base/*/*/base_*_m.gd* detail/*/*/detail_*_m.png* label/*/*/label_*_m.gd*
# base/*/*/base_*_m.gd* detail/*/*/detail_*_m.png* label/*/*/label_*_m.gd* base/*/*/base_*_m.png* label/*/*/label_*_m.png*
#print "END";
#exit;

?>
