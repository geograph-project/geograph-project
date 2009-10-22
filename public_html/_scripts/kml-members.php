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
require_once('geograph/kmlfile.class.php');
require_once('geograph/kmlfile2.class.php');
require_once('geograph/conversions.class.php');

if (!isLocalIPAddress())
{
	init_session();
        $USER->mustHavePerm("admin");
}

$conv = new Conversions;	
$db = GeographDatabaseConnection(true);
			
$kml = new kmlFile();
$kml->atom = true;
$stylefile = "http://{$CONF['KML_HOST']}/kml/style.kmz";

$kml->filename = "Geograph-Members.kml";
$folder = $kml->addChild('Document');
$folder->setItem('name',"Geograph British Isles Members");



$users = $db->GetAssoc("select
		user.user_id,nickname,user.realname,images,gs.x,gs.y,gs.reference_index
		from user
			left join user_stat using (user_id)
			inner join gridsquare gs on (home_gridsquare = gridsquare_id)
		order by realname");

foreach ($users as $user_id => $user) {

	list($wgs84_lat,$wgs84_long) = $conv->internal_to_wgs84($user['x'],$user['y']);
				

	$point = new kmlPoint($wgs84_lat,$wgs84_long);			

	$placemark = new kmlPlacemark($user_id,$user['realname'].' :: '.$user['images'],$point);
	$placemark->useCredit($entry['realname'],"http://{$_SERVER['HTTP_HOST']}/profile/$user_id");
	if (empty($user['nickname'])) {
		$placemark->setItemCDATA('description',$placemark->link);
	} else {
		$placemark->setItemCDATA('description',"Nickname: {$user['nickname']}<br/>{$placemark->link}");
	}
	$placemark->useHoverStyle('def');

	$folder->addChild($placemark);
}

$base=$_SERVER['DOCUMENT_ROOT'];
$file = "/kml/members.kmz";
$kml->outputFile('kmz',false,$base.$file);

if (isset($_GET['debug'])) {
	print "<a href=?download>Open in Google Earth</a><br/>";
	print "<textarea rows=35 style=width:100%>";
	print $kml->returnKML();
	print "</textarea>";
} 
exit;


?>
