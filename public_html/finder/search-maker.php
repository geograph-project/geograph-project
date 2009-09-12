<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');
require_once('geograph/searchenginebuilder.class.php');

$data = array();
$data['searchq'] = '';
$error = false;

$db = GeographDatabaseConnection(true);

$pid = intval($_GET['placename']);


$place = $db->GetRow("select * from placename_index where id=".$pid);
if (empty($place)) {
	die("unable to identify placename");
}

$q = "{$place['gr']} {$place['name']}";

$sphinx = new sphinxwrapper($q);
$sphinx->pageSize = 40;
$sphinx->processQuery();

$ids = $sphinx->returnIds(1,'sqim');

if (empty($ids)) {
	die("unable to identify images");
}
$id_str = implode(',',$ids);

$grs = $db->GetCol("select grid_reference from gridsquare where gridsquare_id in ($id_str)");

$gr_str = implode('|',$grs);

$data['description'] = "near {$place['name']}, {$place['gr']}";
$data['searchq'] = $gr_str;

if (!$error) {
	if (empty($data['orderby'])) {
		$data['orderby'] = 'gridimage_id';
		if (!preg_match('/\w*(\d{4})/',$_GET['first']))
			$data['reverse_order_ind'] = '1';
	}

	if (!empty($_GET['u']))
		$data['user_id'] = $_GET['u'];

	$data['adminoverride'] = 1;
	$data['searchclass'] = 'Text';
	
	$engine = new SearchEngineBuilder('#');
	$engine->page = "search.php";
	if (isset($_GET['rss'])) {
		$engine->page = "syndicator.php";
	} elseif (isset($_GET['kml'])) {
		$engine->page = "kml.php";
	}
	$engine->buildAdvancedQuery($data);

	die("unable to create search");
}


die("unknown error");

?>
