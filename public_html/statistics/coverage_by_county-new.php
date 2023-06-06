<?php
/**
 * $Project: GeoGraph $
 * $Id: images.php 2380 2006-08-13 10:41:07Z barry $
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

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$table = (isset($_GET['table']) && is_numeric($_GET['table']))?intval($_GET['table']):0;
	$smarty->assign('whichtable',$table);

	$template='statistics_tables_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".$table.csv\"");

	$cacheid='statistics|coverage_by_county-new.'.$table;
} else {
	$template='statistics_tables.tpl';

	$cacheid='statistics|coverage_by_county-new';
}

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid)) {
	dieUnderHighLoad();

	$db = GeographDatabaseConnection(true);
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$tables = array();

	###################

	$table = array();

		$table['title'] = "Great Britain";

			$table['table']=$db->GetAll("
			select
				DESCRIPTIO as Description,
				NAME as Name,
				count(distinct hectad) as hectads,
				sum(h.bound_images) as geographs,
				sum(h.area) as area_m2,
				sum(h.bound_images)/sum(h.area)*1000000 as geographs_per1km_square
			from full_county_hectad h inner join full_county c using (auto_id)
			group by NAME order by 6 desc
			" );


			$table['footnote'] = "This table is using Modern <a href=\"/faq.php#counties\">Administrative Counties</a>";


		$table['total'] = count($table['table']);

	$tables[] = $table;

	###################

	$table = array();

		$table['title'] = "Northern Ireland";

		$table['table']=$db->GetAll("
			select
				CountyName as Name,
				count(distinct hectad) as hectads,
				sum(h.bound_images) as geographs,
				sum(h.area) as area,
				sum(h.bound_images)/sum(h.area)*1000000 as geographs_per1km_square
			from ni_counties_hectad h inner join ni_counties c using (auto_id)
			group by CountyName order by 5 desc
		" );

		$table['total'] = count($table['table']);

	$tables[] = $table;

	###################

	$table = array();

		$table['title'] = "Republic of Ireland";

		$table['table']=$db->GetAll("
			select
				PROVINCE AS Province
				COUNTY as Name,
				count(distinct hectad) as hectads,
				sum(h.bound_images) as geographs,
				sum(h.area) as area,
				sum(h.bound_images)/sum(h.area)*1000000 as geographs_per1km_square
			from ireland_counties_hectad h inner join ireland_counties c using (auto_id)
			group by COUNTY order by Province,6 desc
		" );

		$table['total'] = count($table['table']);

	$tables[] = $table;

	###################

	$smarty->assign_by_ref('tables', $tables);

	$smarty->assign("headnote","See also <a href=\"/statistics/coverage_by_country.php\">Coverage by Country</a>. This data is computed using the exact county borders");

	$smarty->assign("h2title",'Coverage by Exact County');
}

$smarty->display($template, $cacheid);

