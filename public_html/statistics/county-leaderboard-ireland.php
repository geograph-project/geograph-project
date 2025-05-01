<?php
/**
 * $Project: GeoGraph $
 * $Id: busyday.php 6824 2010-09-15 19:50:33Z geograph $
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

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
	$template='statistics_table.tpl';
}

$cacheid='statistics|'.basename($_SERVER['PHP_SELF']);

if (!$smarty->is_cached($template, $cacheid))
{
        $db = GeographDatabaseConnection(true);

		$table=$db->GetAll("
		select country,county,island_name,count(*) as places,concat(format(sum(images>2)/count(*)*100,1),'%') as percent,sum(images<3) as unphotographed
			,group_concat(if(images<3,name,NULL) separator ', ' limit 3) as examples
		 from ie_open_places group by county,island_name order by unphotographed
		");

	$title = "Ireland County Leaderboard";

	$smarty->assign_by_ref('table', $table);

	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));

}
//$smarty->assign("filter",2);
//$smarty->assign("nosort",1);
$smarty->display($template, $cacheid);

