<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

############################################

$param = array(
	'execute'=>0,
	'verbose'=>0,
	'src' => "/tmp/197537_f6908b09_213x160.jpg",
	'dst' => "photos/19/75/197537_f6908b09_213x160.jpg.test.jpg", //a path 'relative' to document-root!
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($filesystem)) //eventually gloabl will do this!
	$filesystem = new FileSystem(); //sets up configuation automagically

if (!empty($param['verbose'])) {
	$GLOBALS['curl_verbose'] = $param['verbose'];
	print_r($filesystem);
}

############################################

$destination = $_SERVER['DOCUMENT_ROOT']."/".$param['dst']);

$r = $filesystem->getBucketPath($destination);

print_r($r);

if (empty($param['execute']))
	exit;

############################################

$r = $filesystem->copy($param['src'], $destination);

print_r($r);

