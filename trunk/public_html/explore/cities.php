<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
require_once('geograph/mapmosaic.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;
	
$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'cities';
	

$template='explore_counties.tpl';
$cacheid='explore|counties'.$type;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	if ($type == 'cities') {
		$smarty->assign("page_title", "Notable Selection of Towns/Cities");
		$smarty->assign("extra_info", "* These are just the main towns/cities Geograph knows about for map plotting purposes, it may not be a complete list! The square choosen is probably only an arbitary centre point.");
		$counties = $db->GetAll("SELECT * FROM `loc_towns` WHERE `s` = '1' ORDER BY n");
		
		foreach ($counties as $i => $row) {
			list($x,$y) = $conv->national_to_internal($row['e'],$row['n'],$row['reference_index']);
			$sql="select * from gridimage_search where x=$x and y=$y ".
				" order by moderation_status+0 desc,seq_no limit 1";

			$rec=$db->GetRow($sql);
			if (count($rec))
			{
				$gridimage=new GridImage;
				$gridimage->fastInit($rec);
				
				$gridimage->county = $row['name'];
				
				$results[] = $gridimage;
			}
			else 
			{
				$sql="select grid_reference from gridsquare where x=$x and y=$y limit 1";
				
				$rec=$db->GetRow($sql);
				if (count($rec)) 
				{
					$rec['county'] = $row['name'];
					$unfilled[] = $rec;
				}
			}
		}
		
	}

	$smarty->assign_by_ref("results", $results);	
	$smarty->assign_by_ref("unfilled", $unfilled);	
}


$smarty->display($template, $cacheid);

	
?>
