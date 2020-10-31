<?php
/**
 * $Project: GeoGraph $
 * $Id: process_events.php 5211 2009-01-24 20:44:18Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

$id = intval(time()/1787);
$id2 = intval($_GET['id']);
$hash = hash_hmac('sha1',$id2,$CONF['register_confirmation_secret']);
if ($_GET['hash'] != $hash || abs($id-$id2) > 3) {
        die("[]");
}

$data = array();

if ($_GET['db'] == 'master') {
	$db = GeographDatabaseConnection(false);

	if ($db)
		$data['connected'] = true;

} elseif ($_GET['db'] == 'slave') {
	if (empty($CONF['db_read_driver'])) {
		$data['error'] = 'undefined';
	} else {
		$db = GeographDatabaseConnection(true);

		if ($db && $db->readonly) {
			$data['connected'] = true;
		}
	}
} elseif ($_GET['db'] == 'two') {
	//not used any more
} elseif ($_GET['db'] == 'file') {
	if (empty($CONF['filesystem_dsn'])) {
		$data['error'] = 'undefined';
	} else {
		$db=NewADOConnection($CONF['filesystem_dsn']);

		if ($db)
	                $data['connected'] = true;
	}
} else {
	die("{}");
}

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($data['connected'])) {
	if (!empty($_GET['ps']))
		$data['ps'] = $db->getAll("SHOW PROCESSLIST");
	if (!empty($_GET['slave']))
		$data['slave'] = $db->getRow("SHOW SLAVE STATUS");
	if (!empty($_GET['master']))
		$data['master'] = $db->getRow("SHOW MASTER STATUS");
}


outputJSON($data);


