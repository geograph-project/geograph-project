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
require_once('geograph/conversions.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

$conv = new Conversions();


	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	
	
?>
<h2>gridimage.placename_id Rebuild Tool</h2>
<form action="buildplacename_id.php" method="post">
<input type="checkbox" name="firsts"/> Only do firsts<br/>
Start: <input type="text" name="start"/> <br/>
<input type="submit" name="go" value="Start">
</form>

<?php

if (isset($_POST['go']))
{
	echo "<h3> Rebuilding gridimage.placename_id...</h3>";
	flush();
	set_time_limit(3600*24);
	
	
	$tim = time();
		 
	$count=0;
	
	if (!empty($_POST['start'])) {
		$limit = " LIMIT {$_POST['start']},99999999";
	} 
	
	if (isset($_POST['firsts'])) {
		$recordSet = &$db->Execute("select * from gridimage where moderation_status = 'geograph' and ftf = 1 $limit");
	} else {
		$recordSet = &$db->Execute("select * from gridimage $limit");
	}
	
	while (!$recordSet->EOF) 
	{
		$image=new GridImage;
		$gid = $recordSet->fields['gridimage_id'];
		$image->_initFromArray($recordSet->fields);
		
		$square = $image->grid_square;
		if (!isset($square->nateastings))
			$square->getNatEastings();

		//to optimise the query, we scan a square centred on the
		//the required point
		$radius = 100000;
		
		$left=$square->nateastings-$radius;
		$right=$square->nateastings+$radius;
		$top=$square->natnorthings-$radius;
		$bottom=$square->natnorthings+$radius;
		
		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";
		
		if ($CONF['use_gazetteer'] == 'OS' && $square->reference_index == 1) {
			$places = $db->GetRow("select
					(seq + 1000000) as pid,
					power(east-{$square->nateastings},2)+power(north-{$square->natnorthings},2) as distance
				from
					os_gaz
				where
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en) AND
					f_code in ('C','T')
				order by distance asc,f_code+0 asc limit 1");
		} else if ($CONF['use_gazetteer'] == 'towns' && $square->reference_index == 1) {
			$places = $db->GetRow("select
					(id + 900000) as pid,
					power(e-{$square->nateastings},2)+power(n-{$square->natnorthings},2) as distance
				from 
					loc_towns
				where
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en) AND
					reference_index = {$square->reference_index}
				order by distance asc limit 1");
		} else {
			$places = $db->GetRow("select
					loc_placenames.id as pid,
					power(e-{$square->nateastings},2)+power(n-{$square->natnorthings},2) as distance
				from 
					loc_placenames
				where
					dsg = 'PPL' AND 
					CONTAINS( 	
						GeomFromText($rectangle),
						point_en) AND
					loc_placenames.reference_index = {$square->reference_index}
				order by distance asc limit 1");
		}

		$pid = $places['pid'];
			
		if ($pid)
			$db->Execute("update LOW_PRIORITY gridimage set placename_id = $pid,upd_timestamp = '{$recordSet->fields['upd_timestamp']}' where gridimage_id = $gid");
				
		if (++$count%500==0) {
			printf("done %d at <b>%d</b> seconds<BR>",$count,time()-$tim);
			flush();
			sleep(2);
		}
		
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
}

$smarty->display('_std_end.tpl');
exit;
	


	
?>
