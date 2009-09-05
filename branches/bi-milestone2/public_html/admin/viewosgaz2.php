<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/gazetteer.class.php');

init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);


$smarty->display('_std_begin.tpl');
	
	
	
if (!empty($_GET['gridref'])) {	

//////////////////////////////	
	
	$square=new GridSquare;
	
	$grid_ok=$square->setByFullGridRef($_GET['gridref'],true);

	$e = $square->nateastings+500;
	$n = $square->natnorthings+500;

	$radius = ((isset($_GET['radius']) && is_numeric($_GET['radius']))?min(10,intval($_GET['radius'])):2)*1000+5;


//////////////////////////////
	
	//to optimise the query, we scan a square centred on the
	//the required point
	$left=$e-$radius;
	$right=$e+$radius;
	$top=$n-$radius;
	$bottom=$n+$radius;

	$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

	$places = $db->GetAll("select
			def_nam,f_code,east,north,km_ref,full_county
		from
			os_gaz
		where
			CONTAINS( 	
				GeomFromText($rectangle),
				point_en)
		order by north desc, east asc, f_code+0,def_nam");
	
	print "<table border=1>";
	
	$laste =0;
	$lastn =0;
	$mine = 9999999999;
	foreach ($places as $i => $p) {
		if ($laste && $p['east'] != $laste) {
			print "</td>";
		}
		if ($p['north'] != $lastn) {
			if ($lastn)
				print "</tr>";
			print "<tr>";
			if ($laste && $p['east']-$mine > 1000) {
				print str_repeat("<td></td>",($p['east']-$mine)/1000);
			}
			print "<td><b>{$p['km_ref']}</b><br/>";
		} elseif ($p['east'] != $laste) {
			if ($p['east']-$laste > 1000) {
				print str_repeat("<td></td>",($p['east']-$laste)/1000-1);
			}
			print "<td><b>{$p['km_ref']}</b><br/>";
		}
		print "{$p['f_code']}.{$p['def_nam']},{$p['full_county']}<br/>";
		$laste = $p['east'];
		$mine = min($mine,$laste);
		$lastn = $p['north'];
	}
	if ($laste) {
		print "</td>";
	}
	if ($lastn)
		print "</tr>";
	print "</table>";
}			
	
//////////////////////////////

$smarty->display('_std_end.tpl');
exit;
	



	
?>
