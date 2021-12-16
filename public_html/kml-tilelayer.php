<?php
/**
 * $Project: GeoGraph $
 * $Id: kml.php 8784 2018-07-05 10:25:16Z barry $
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

$smarty = new GeographPage;

$template='kml-tilelayer.tpl';
$cacheid = '';

##################################

$tiles = array(
        "Coverage" => array(
                'url' => 'https://t0.geograph.org.uk/tile/tile-coverage.php?z=$[level]&x=$[x]&y=$[y]',
                'min' => 5, 'max' => 12,
                'personalized' => '&user_id=$[user_id]',
        ),
        //large version: 'min' => 13, 'max' => 15,
        //z<7 redirects to tile-coverage-hectad.php
        //z>11 redirects to tile-coverage-large.php

        "Subjects" => array(
                'url' => 'https://t0.geograph.org.uk/tile/tile-density.php?z=$[level]&x=$[x]&y=$[y]&match=&l=1&6=1',
                'min' => 6, 'max' => 21,
                'personalized' => '&user_id=$[user_id]',
                'germany' => '&gg=1',
                'islands' => '&is=1',
        ),
        //z<7 redirects to tile-hectad.php
        //z<10 redirects to tile-square.php
        // old version tile.php

        "Viewpoints" => array(
                'url' => 'https://t0.geograph.org.uk/tile/tile-viewpoint2.php?z=$[level]&x=$[x]&y=$[y]&match=&l=1&6=1&j=0',
                'min' => 10, 'max' => 21,
                'personalized' => '&user_id=$[user_id]',
                'germany' => '&gg=1',
                'islands' => '&is=1',
        ),
        //set j=1 when z>16 && subjects enabled to get viewpoint lines!
        // old version tile-viewpoint.php  (different arrow)
        // old cersion tile-viewpoint-dot.php (simple dots)

        "Opportunities" => array(
                'url' => 'https://t0.geograph.org.uk/tile/tile-score.php?z=$[level]&x=$[x]&y=$[y]',
                'min' => 10, 'max' => 18,
                'personalized' => '&user_id=$[user_id]',
        ),

        "PhotoMap" => array(
                'url' => 'https://t0.geograph.org.uk/tile/tile-photomap.php?z=$[level]&x=$[x]&y=$[y]&match=&6=1&gbt=6',
                'min' => 10, 'max' => 18,
                'personalized' => '&user_id=$[user_id]',
        ),

        "Scenicness" => array(
                'url' => 'https://t0.geograph.org.uk/tile/tilescenic.php?z=$[level]&x=$[x]&y=$[y]&l=1&group=auto&column=avg&text=2',
                'min' => 16, 'max' => 20,
		'coverage' => 'Greater London',
		'index' => 'scenic',
        ),

	"ChannelIslandsCoverage" => array(
		'url' => 'https://www.geograph.org.gg/tile/tile-coverage.php?z=$[level]&x=$[x]&y=$[y]',
		'min' => 5, 'max' => 18,
		'index' => 'islands',
	),
	"GermanyCoverage" => array(
		'url' => 'https://geo-en.hlipp.de/tile.php?x=$[x]&y=$[y]&Z=$[level]&l=2&o=1&t=',
		'min' => 4, 'max' => 14,
		'index' => 'germany',
	),

);

$Template = "
        <GroundOverlay>
                <name>\$name</name>
                <Icon>
                        <href>data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==</href>
                </Icon>
                <LatLonBox>
                        <north>\$north</north>
                        <south>\$south</south>
                        <east>\$east</east>
                        <west>\$west</west>
                </LatLonBox>
                <gx:MapTilePyramid>
                        <Link>
                                <href>\$urlencode</href>
                        </Link>
                        <gx:minLevel>\$min</gx:minLevel>
                        <gx:maxLevel>\$max</gx:maxLevel>
                </gx:MapTilePyramid>
        </GroundOverlay>
        ";

##################################

if (!empty($_GET['layer'])) {
	$bits = explode('_',$_GET['layer']);
	$base = array_pop($bits);
	if (!empty($tiles[$base])) {
		$data = $tiles[$base];

		$name = $base;
		$prefix = "Geograph Britain and Ireland";

		$count = 0;
		$name = preg_replace('/ChannelIslands/','',$name,1,$count);
		if ($count)
			$prefix = "Geograph Channel Islands";
		$name = preg_replace('/Germany/','',$name,1,$count);
		if ($count)
			$prefix = "Geograph Germany";

		if (!empty($bits[0])) {
			if ($bits[0] == 'personal') {
				$data['url'] .= str_replace('$[user_id]',$USER->user_id, $data['personalized']);
                                $prefix = 'For '.htmlentities2(latin1_to_utf8($USER->realname), ENT_COMPAT, 'UTF-8');
			} elseif ($bits[0] == 'germany') {
				$data['url'] .= $data['germany'];
				$prefix = "Geograph Germany";
				$data['index'] = 'germany';
			} elseif ($bits[0] == 'islands') {
				$data['url'] .= $data['islands'];
				$prefix = "Geograph Channel Islands";
				$data['index'] = 'islands';
			}
		}
		if (!empty($_GET['q']) && preg_match('/match=/',$data['url']))
			$data['url'] = preg_replace('/match=([^&]*)/',"match=".urlencode($_GET['q']),$data['url']);

		if (!empty($data['coverage']))
			$base .= ", ".$data['coverage'];

		$name .= " ($prefix)";


		if (empty($data['index']))
			$data['index'] = 'gi_stemmed';
		$sph = GeographSphinxConnection('sphinxql',true);
		$row = $sph->getRow($sql = "select min(wgs84_lat) as `south`,min(wgs84_long) as `west`,max(wgs84_lat) as `north`,max(wgs84_long) as `east` from {$data['index']}");

		$kml = $Template;
		$kml = preg_replace_callback('/\$(north|south|east|west)/',function($m) {
	            return $GLOBALS['row'][$m[1]] * 57.29577951308232;
        	}, $kml);
		$kml = str_replace('$name',$name, $kml);
		$kml = str_replace('$urlencode',htmlentities($data['url']), $kml);
		$kml = str_replace('$min',$data['min'], $kml);
		$kml = str_replace('$max',$data['max'], $kml);

//		header("Content-Type: text/plain");
		header("Content-type: application/vnd.google-earth.kml");
		header("Content-Disposition: attachment; filename=\"geographtiles-".preg_replace('/[^\w]+/','',$name).".kml\"");


print <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Document>
	<name>Geograph Tiles :: $name</name>
$kml
</Document>
</kml>
EOT;
		exit;
	}
}

##################################

$list = array();
foreach ($tiles as $name => $data) {
	$list[$name] = $name;
	if (!empty($data['personalized']) && $USER->user_id) { //todo check user_stat!
		$list["personal_$name"] = "$name :: just for ".htmlentities($USER->realname);
	}
}

foreach ($tiles as $name => $data) {
	if (!empty($data['germany'])) {
		$list["germany_$name"] = "Germany :: $name";
	}
}

foreach ($tiles as $name => $data) {
	if (!empty($data['islands'])) {
		$list["islands_$name"] = "Channel Islands :: $name";
	}
}

$smarty->assign('list', $list);

##################################


$smarty->display($template, $cacheid);


