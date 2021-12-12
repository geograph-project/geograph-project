<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
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

$hash = substr(hash_hmac('md5', date('Y-m-d'), $_SERVER['CONF_REGISTER_CONFIRMATION_SECRET']),0,8);

if (empty($_GET['hash']) || $_GET['hash'] != $hash) {
	header("HTTP/1.1 401 Unauthorized");
	exit;
}


if (function_exists('apc_store') && !empty($_GET['clear'])) {
	print apc_delete('lag_warning');
}

if (function_exists('apc_store') && isset($_GET['cool'])) {
	if (empty($_GET['cool'])) {
		print apc_delete('lag_cooloff');
	} else {
		print apc_store('lag_cooloff',1,intval($_GET['cool']));
	}
	if (!empty($_GET['q'])) {
		$hostname=trim(`hostname`);
		print ". Host = $hostname";
		exit;
	}
}


