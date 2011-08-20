<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7361 2011-08-11 23:11:50Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_GET['callback'])) {
	header('Content-type: text/javascript');
} else {
	header('Content-type: application/json');
}

customExpiresHeader(3600*24);


$db = GeographDatabaseConnection(true);

$query = "SELECT 'top' as `prefix`, top AS tag
		FROM category_primary 
		ORDER BY sort_order";

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$data = $db->getAll($query);

if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($data);

if (!empty($_GET['callback'])) {
        echo ");";
}



