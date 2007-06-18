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

$template='statistics_tables.tpl';

$cacheid='coverage_by_county';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid)) {
	dieUnderHighLoad();
	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$tables = array();
	
	###################

	$table = array();
	
		$table['title'] = "Great Britain";

		$table['table']=$db->GetAll("
		select 
			full_county as County,
			loc_country.name as Country,
			count(*) as `Grid Squares`,
			sum(has_geographs) as `Geographed`,
			sum(has_geographs)/count(*)*100 as Percentage
		from gridsquare gs
			inner join os_gaz on (placename_id-1000000 = os_gaz.seq)
			inner join os_gaz_county on (full_county = name)
			inner join loc_country on (country = loc_country.code)
		where gs.reference_index = 1 and percent_land > 0
		group by co_code
		" );

		foreach ($table['table'] as $id => $row) {
			
		}

		$table['total'] = count($table);
		
		$table['footnote'] = "This table is using Modern <a href=\"/faq.php#counties\">Administrative Counties</a>";


	$tables[] = $table;

	###################

	$table = array();
	
		$table['title'] = "Ireland";

		$table['table']=$db->GetAll("
		select 
			loc_adm1.name as County,
			loc_country.name as Country,
			count(*) as `Grid Squares`,
			sum(has_geographs) as `Geographed`,
			sum(has_geographs)/count(*)*100 as Percentage
		from gridsquare gs
			inner join loc_placenames on (placename_id = id)
			inner join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and loc_adm1.country = loc_placenames.country)
			inner join loc_country on (loc_placenames.country = loc_country.code)
		where gs.reference_index = 2 and percent_land > 0
		group by loc_adm1.adm1
		");

		$table['total'] = count($table);

	$tables[] = $table;

	###################
	
	$smarty->assign_by_ref('tables', $tables);
	
	$smarty->assign("headnote","See also <a href=\"/statistics/coverage_by_country.php\">Coverage by Country</a>.");
			
	$smarty->assign("h2title",'Coverage by County');
} 

$smarty->display($template, $cacheid);

?>
