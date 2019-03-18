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

#########################################
# general page startup

require_once('geograph/global.inc.php');

if (empty($_GET['q'])) {
        die("[]");
}
if (!empty($_GET['callback'])) {
        die("['error']"); //hack to prevent others abusing this script?
}

$qu = urlencode(trim($_GET['q']));
$data= array();

	$body = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=$qu&key={$CONF['google_maps_api3_server']}&region=uk");
	$decode = json_decode($body);

	if ($decode && $decode->results && $decode->status == 'OK') {
		$r = reset($decode->results);
		$coord = $r->geometry->location->lat.','.$r->geometry->location->lng;

		$data['coord'] = $coord;
		//we build the full url server side, to include OUR key :)
		$data['map1'] = "https://maps.googleapis.com/maps/api/staticmap?markers=size:mid|$coord&zoom=13&key={$CONF['google_maps_api3_key']}&size=250x120&maptype=terrain";
		$data['map2'] = "https://maps.googleapis.com/maps/api/staticmap?markers=size:mid|$coord&zoom=7&key={$CONF['google_maps_api3_key']}&size=250x120&maptype=terrain";

		$data['address'] = $r->formatted_address;
	}

if (!empty($_SERVER['HTTP_ORIGIN'])
        && preg_match('/^https?:\/\/(www|schools)\.geograph\.(org\.uk|ie)\.?$/',$_SERVER['HTTP_ORIGIN'])) { //can be spoofed, but SOME protection!

        header('Access-Control-Allow-Origin: *'); //although now this allows everyone to access it!
}

customExpiresHeader(360000);
outputJSON($data);

