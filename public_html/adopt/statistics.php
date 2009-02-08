<?php
/**
 * $Project: GeoGraph $
 * $Id: totals.php 4220 2008-03-09 11:58:12Z barry $
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

require_once('geograph/global.inc.php');

init_session();

$smarty = new GeographPage;


$template='adopt_statistics.tpl';
$cacheid='';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 600; //10min cache

if (!$smarty->is_cached($template, $cacheid))
{
	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;


	$stats= $db->GetAssoc("select status,
			count(*) as count, 
			count(distinct user_id) as users, 
			count(distinct hectad) as hectads 
		from hectad_assignment group by status");
	
	
	$smarty->assign_by_ref('stats', $stats);
}



$smarty->display($template, $cacheid);

	
?>