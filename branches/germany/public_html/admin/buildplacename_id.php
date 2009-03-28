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

if (isset($_POST['crit']))
	$crit = $_POST['crit'];
else 
	$crit = "placename_id = 0";

?>
<h2>placename_id Rebuild Tool</h2>
<form action="buildplacename_id.php" method="post">
select * from <select name="table"><option>gridimage</option>
<option<?php if ($_POST['table'] == 'gridsquare') echo " selected"; ?>>gridsquare</option></select> where <input type="text" name="crit" size="60" value="<?php echo $crit; ?>"/><br/>
(if reference gs will join gridsquare gs)<br/>
<input type="submit" name="go" value="Start"/>
<input type="checkbox" name="file"/> Write results to file (otherwise writes to DB)
</form>

<?php

if (isset($_POST['go']))
{
	$table = $_POST['table'];
	
	echo "<h3> Rebuilding $table.placename_id...</h3>";
	flush();
	set_time_limit(3600*24);
	
	
	$tim = time();
		 
	$count=0;
	
	$limit  = '';
	if (!empty($_POST['start']))
		$limit = " LIMIT {$_POST['start']},99999999";

	$join = '';
	if (preg_match('/\bgs\b/',$_POST['crit']))
		$join .= " inner join gridsquare gs using (gridsquare_id)";
	if (preg_match('/\bgi\b/',$_POST['crit']))
		$join .= " inner join gridimage gi using (gridsquare_id)";
	if (preg_match('/\bu\b/',$_POST['crit']))
		$join .= " inner join user u using (user_id)";
	

	$recordSet = &$db->Execute("select * from $table $join where {$_POST['crit']} $limit");
	if (!empty($_POST['file'])) {	
		$handle = fopen($_SERVER['DOCUMENT_ROOT']."/rss/placename_updates.sql",'a') or die("unable to open file");
	}
	
	while (!$recordSet->EOF) 
	{
		if ($table == 'gridimage') {
			$image=new GridImage;
			$gid = $recordSet->fields['gridimage_id'];
			$image->_initFromArray($recordSet->fields);

			$square = $image->grid_square;
			$extra = ",upd_timestamp = '{$recordSet->fields['upd_timestamp']}'";
		
			if (!isset($square->nateastings))
				$square->getNatEastings();

			//to optimise the query, we scan a square centred on the
			//the required point
			$radius = 30000;

			$places = $square->findNearestPlace($radius);
			$pid = $places['pid'];		
		} else {
			$gid = $recordSet->fields['gridsquare_id'];
			#$from_stratch = 1;
			if ($from_stratch || $recordSet->fields['reference_index'] == 2) {
				$square=new GridSquare;
				#$square->_initFromArray($recordSet->fields);
				//store cols as members
				foreach($recordSet->fields as $name=>$value) {
					if (!is_numeric($name))
						$square->$name=$value;
				}
				$square->_storeGridRef($square->grid_reference);
				$extra = "";
			
				if (!isset($square->nateastings))
					$square->getNatEastings();

				//to optimise the query, we scan a square centred on the
				//the required point
				$radius = 100000;

				$places = $square->findNearestPlace($radius);
				$pid = $places['pid'];		
			} else {
				//to optimise the query, we scan a square centred on the
				//the required point
				$radius = 100000;
				
				
				$left=$recordSet->fields['x']-$radius;
				$right=$recordSet->fields['x']+$radius;
				$top=$recordSet->fields['y']-$radius;
				$bottom=$recordSet->fields['y']+$radius;

				$ofilter=" and placename_id>0 ";

				$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

				$sql="select placename_id,
					power(x-{$recordSet->fields['x']},2)+power(y-{$recordSet->fields['y']},2) as distance
					from gridsquare where
					CONTAINS( 	
						GeomFromText($rectangle),
						point_xy)
					$ofilter
					order by distance asc limit 1";

				$square = $db->GetRow($sql);
				$pid = 0;
				if (count($square) && ($distance = sqrt($square['distance'])) && ($distance <= $radius))
				{
					$pid = $square['placename_id'];
				}
			} 
			
		}

		if ($pid) {
			if (empty($_POST['file'])) {	
				$db->Execute("update LOW_PRIORITY gridimage set placename_id = $pid,upd_timestamp = '{$recordSet->fields['upd_timestamp']}' where gridimage_id = $gid");
			} else {
				fwrite($handle,"update $table set placename_id = $pid$extra where {$table}_id = $gid;\n");
			}
		}
				
		if (++$count%500==0) {
			printf("done %d at <b>%d</b> seconds<BR>",$count,time()-$tim);
			flush();
			sleep(2);
		}
		
		$recordSet->MoveNext();
	}
	$recordSet->Close(); 
	if ($handle)
		fclose($handle);
}

$smarty->display('_std_end.tpl');
exit;
	


	
?>
