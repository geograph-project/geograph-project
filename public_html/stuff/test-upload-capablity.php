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

require_once('geograph/uploadmanager.class.php');
$up = new UploadManager(); //for now only use for _isJpeg, _downsizeFile is designed to work with the upload_tmp_dir

ini_set('display_errors',1);

$data = array();
$data['post_length'] = $_SERVER['CONTENT_LENGTH'];

if (!empty($_FILES['jpeg'])) {
	$data['filesize'] = filesize($_FILES['jpeg']['tmp_name']);
	$data['is_jpeg'] = $up->_isJpeg($_FILES['jpeg']['tmp_name']);

	if (!empty($_GET)) {
		$s=getimagesize($_FILES['jpeg']['tmp_name']);
		$data['width']=$s[0];
		$data['height']=$s[1];
	}

	$max_dimension = 1024;
	$source = $_FILES['jpeg']['tmp_name'];
	$dest = tempnam('/tmp/','img');

	///////////////////////////////////

	//code extacted from $up->_downsizeFile ...

	if (!empty($_GET['convert']) && $data['width'] < 5000 && $data['height'] < 5000) {
		$cmd = sprintf("\"%sconvert\" -resize %ldx%ld -quality 87 -strip jpg:%s jpg:%s", $CONF['imagemagick_path'],$max_dimension, $max_dimension, $source, $dest);

		$start = microtime(true);
		passthru($cmd);
		$end = microtime(true);
		$data['convert_time'] = sprintf('%.3f',$end-$start);
		$data['convert_size'] = filesize($dest);
	}

	///////////////////////////////////

	if (!empty($_GET['vips'])) {
                                        $cmd = array();
                                        $cmd[] = "vipsthumbnail";
                                        $cmd[] = "%s"; //--iprofile /var/www/geograph/libs/3rdparty/cmyk.icm";
                                        $cmd[] = "-s {$max_dimension}x{$max_dimension}";
                                        $cmd[] = "--interpolator bicubic"; //the default is bilinear
                                        //$cmd[] = "--eprofile /usr/share/color/icc/sRGB.icc --delete"; //fails on monocrome!
                                        if ($width < 3000 && $height < 3000)
                                                $cmd[] = "--linear"; //its slow on big images!

                                        $cmd[] = "-o %s[strip,Q=87]"; //this is creating an intermediate thumbnail, so ok to strip exif

                                        $cmd = sprintf(implode(' ',$cmd), $filename, "$dest.jpg"); //note, vips needs the file extension to write it

		$start = microtime(true);
		passthru($cmd);
		$end = microtime(true);
		$data['vips_time'] = sprintf('%.3f',$end-$start);
		$data['vips_size'] = filesize("$dest.jpg");
	}

	///////////////////////////////////

	if (!empty($dest)) {
		if (file_exists($dest))		unlink($dest);
		if (file_exists("$dest.jpg"))	unlink("$dest.jpg"); //note, vips needs the file extension to write it
	}
}


outputJSON($data);
print "\n";

