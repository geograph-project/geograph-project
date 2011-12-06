<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 6407 2010-03-03 20:44:37Z barry $
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

$results = array();

customExpiresHeader(360000);

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

        $sphinx = new sphinxwrapper();

        $cl = $sphinx->_getClient();

        $results = $cl->BuildKeywords($q,"{$CONF['sphinx_prefix']}gi_stemmed", true);
} else {
	$results = "No query!";
}


if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($results);

if (!empty($_GET['callback'])) {
        echo ");";
}

