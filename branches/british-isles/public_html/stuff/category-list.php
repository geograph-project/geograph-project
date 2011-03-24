<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm("basic");

header("Content-Type: text/plain");

$db = GeographDatabaseConnection(true);

if (empty($_GET) && rand(1,10) > 6) {
	$_GET['unused'] = 1;
}


if (isset($_GET['unused'])) {
        if (isset($_GET['full'])) {
		$count = 10000;
	} else {
		$count = 100;
	}
	$col = $db->getCol("SELECT imageclass FROM category_stat LEFT JOIN category_top_log USING (imageclass) WHERE category_map_id IS NULL LIMIT $count");

} elseif (isset($_GET['mine'])) {
        $col = $db->getCol("SELECT imageclass FROM gridimage_search WHERE user_id = {$USER->user_id} GROUP BY imageclass");
} else {
        if (isset($_GET['full'])) {
                $count = 10000;
                $offset = 0;
        }  else {
                $count = 100;
                $end = $db->getOne("SELECT COUNT(*) FROM category_stat");
                #$offset = rand(0,$end-$count-1);

                $db->Execute("REPLACE INTO geograph_tmp.category_stat_sequence SET seq=NULL,a=1"); //poor mans sequence

                $page = $db->getOne("SELECT LAST_INSERT_ID()")-1;

                $pages = intval($end/$count);
                $page = $page%$pages;

                $offset = $page*$count;

        }

        $col = $db->getCol("SELECT imageclass FROM category_stat LIMIT $offset,$count");
}

foreach ($col as $class) {
        print "$class\n";
}






