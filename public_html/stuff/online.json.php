<?php
/**
 * $Project: GeoGraph $
 * $Id: clusters.php 5786 2009-09-12 10:18:04Z barry $
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
init_session();

$_GET['callback'] = '';

header('Access-Control-Allow-Origin: https://m.geograph.org.uk');
header('Access-Control-Allow-Credentials: true');

if (isset($_SERVER['HTTP_ORIGIN'])) {
	if ($_SERVER['HTTP_ORIGIN'] != "https://m.geograph.org.uk" && $_SERVER['HTTP_ORIGIN'] != "https://{$_SERVER['HTTP_HOST']}") {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}
}

customExpiresHeader(10);

if ($USER->hasPerm('basic')) {
	$data = 'ok';
} else {
	$data = 'no';
}

outputJSON($data);



