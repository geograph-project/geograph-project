<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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


$smarty->display('_std_begin.tpl');

?>

<h2>Geograph PhotoMap</h2>

<p>The PhotoMap layers, have now been intergrated into the main <a href="/mapper/combined.php">Coverage Map</a>. So dont need this dedicated viewer anymore. 

<p>In the layer switcher top right of the map, select 'Geograph PhotoMap' to get the imagry base layer. You may also want to enable the 'PhotoMap Overlay' which is a basic wireframe map to help orientate. </p>

<?

$smarty->display('_std_end.tpl');


