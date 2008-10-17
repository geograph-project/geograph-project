<?php
/**
 * $Project: GeoGraph $
 * $Id: buildplacename_id.php 2518 2006-09-08 20:33:06Z barry $
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



	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	
	
?>
<h2>gridprefix.geometry_boundary Rebuild Tool</h2>
<form action="buildgisindex.php" method="post">
<input type="radio" name="table" value="gridprefix.geometry_boundary"/> gridprefix [geometry_boundary]<br/>
<input type="submit" name="go" value="Start">
</form>

<?php

if (isset($_POST['go']))
{
	$tim = time();
	set_time_limit(3600*24);

	if ($_POST['table'] == 'gridprefix.geometry_boundary') {
		echo "<h3> Rebuilding gridprefix.geometry_boundary index...</h3>";
		flush();
		

		$recordSet = &$db->Execute("select * from gridprefix");

		while (!$recordSet->EOF) 
		{
			$origin_x=$recordSet->fields['origin_x'];
			$origin_y=$recordSet->fields['origin_y'];
			$w=$recordSet->fields['width'];
			$h=$recordSet->fields['height'];

			//get polygon of boundary relative to corner of square (bot-left)
			if (strlen($recordSet->fields['boundary'])) {
				$polykm=explode(',', $recordSet->fields['boundary']);
			} else {
				$polykm=array(0,0, 0,100, 100,100, 100,0);
			}

			//now convert km to internal refence
			$poly=array();
			$pts=count($polykm)/2;
			for($i=0; $i<$pts; $i++)
			{
				$x=$polykm[$i*2]+$origin_x;
				$y=$polykm[$i*2+1]+$origin_y;
				$poly[] = "$x $y";
			}
			//duplicate the last point
			array_push($poly,$poly[0]);
		

			$boundary = "'POLYGON((".implode(',',$poly)."))'";

			$db->Execute("UPDATE gridprefix SET geometry_boundary = GeomFromText($boundary) WHERE prefix = '".$recordSet->fields['prefix']."'");

			printf("done %s at <b>%d</b> seconds $boundary<BR>",$recordSet->fields['prefix'],time()-$tim);
			flush();
			

			$recordSet->MoveNext();
		}
		$recordSet->Close(); 
	}
}

$smarty->display('_std_end.tpl');
exit;
	


	
?>
