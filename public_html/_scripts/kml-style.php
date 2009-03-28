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


if ( ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) &&
     (strpos($_SERVER['HTTP_X_FORWARDED_FOR'],$CONF['server_ip']) !== 0) )  //begins with
{
	init_session();
        $USER->mustHavePerm("admin");
}




$kml = new kmlFile();
$folder = $kml->addChild('Document');

$Style = $folder->addChild('Style','c2');
$IconStyle = $Style->addChild('IconStyle');
$IconStyle->setItem('scale',0);
$LabelStyle = $Style->addChild('LabelStyle');
$LabelStyle->setItem('color','ff00aaff');


$Style = $folder->addChild('Style','c1');
$IconStyle = $Style->addChild('IconStyle');
$IconStyle->setItem('scale',0);
$LabelStyle = $Style->addChild('LabelStyle');
$LabelStyle->setItem('color','ff0000ff');


$folder->addHoverStyle('def');
$folder->addHoverStyle('p1',1,1.1,'cam1.png;cam1h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p1d',1,1.1,'cam1d.png;cam1dh.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p1r',1,1.1,'cam1r.png;cam1rh.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p1dr',1,1.1,'cam1dr.png;cam1drh.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p2',1,1.1,'cam2.png;cam2h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p3',1,1.1,'cam3.png;cam3h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p4',1,1.1,'cam4.png;cam4h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p10',1,1.1,'cam10.png;cam10h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->addHoverStyle('p20',1,1.1,'cam20.png;cam20h.png',"http://".$CONF['KML_HOST'].'/kml/images/');
$folder->setItem('name',"Styles :: Geograph SuperLayer");


$base=$_SERVER['DOCUMENT_ROOT'];
$file = "/kml/style.kmz";
$kml->outputFile('kmz',false,$base.$file);

$file = "/kml/style.kml";
$kml->outputFile('kml',false,$base.$file);



?>
