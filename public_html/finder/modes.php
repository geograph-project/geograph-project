<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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
init_session();

if (!empty($_GET['rating'])) {

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');

	$ins = "INSERT INTO search_ranking_log SET
		mode = ".intval(@$_GET['mode']).",
		q = ".$db->Quote(@$_GET['q']).",
		comment = ".$db->Quote(@$_GET['comment']).",
		rating = ".intval(@$_GET['rating']).",
		ipaddr = INET_ATON('".getRemoteIP()."'),
		user_id = ".intval($USER->user_id);

	$db->Execute($ins);
	
	die("thanks!");
}


$smarty = new GeographPage;
if (!empty($_GET['q'])) {
	$q = isset($_GET['q'])?$_GET['q']:'';

	$q = preg_replace('/[^\w=]+/',' ',trim(strtolower($q)));
	$smarty->assign("q",$q);
	
	$words = preg_split('/\s+/',$q);
	if (count($words) > 1) {
		
		$inners1 = $inners2 = array();
		foreach (range(1,5) as $key) {
			$inners1[] = array('url'=>"/finder/search-service.php?feedback&mode=$key&q=".urlencode($q));
		}
		foreach (range(6,12) as $key) {
			$inners2[] = array('url'=>"/finder/search-service.php?feedback&mode=$key&q=".urlencode($q));
		}
		shuffle($inners1);
		shuffle($inners2);
		$inners=array_merge($inners1,$inners2);
		$smarty->assign_by_ref("inners",$inners);
		$smarty->assign("count_inners",count($inners));
	}
}

$smarty->display('finder_modes.tpl');

