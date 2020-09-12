<?php
/**
 * $Project: GeoGraph $
 * $Id: glossary.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

$postfix = "";
if (isset($_GET['map'])) {
	$postfix = "/display=map";
}

if ($row = $db->getRow("SELECT content_id,title FROM content WHERE foreign_id = ".intval($_GET['id'])." AND source = ".$db->Quote($_GET['source']))) {
	$id = $row['content_id'];
	$postfix = "/content_title=".urlencode($row['title'])."/content_id=$id".$postfix;
}

header("Location: /browser/#!$postfix");


