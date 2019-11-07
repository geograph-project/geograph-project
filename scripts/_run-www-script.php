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


//these are the arguments we expect
$param=array(
        'script'=>'', //the script to run!
	'get' => null,
);

chdir(__DIR__);
require "./_scripts.inc.php";

if (empty($param['script'])) {
	die("Usage: {$argv[0]} --script=test.php --get=run=1\n\n");
}

if (!empty($param['get'])) {
	$_GET = array();
	parse_str($param['get'], $_GET);
}

chdir($_SERVER['DOCUMENT_ROOT']);
include $param['script'];


