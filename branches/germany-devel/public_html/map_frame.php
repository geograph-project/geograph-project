<?php
/**
 * $Project: GeoGraph $
 * $Id: browse.php 2865 2007-01-05 14:24:01Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/token.class.php');
require_once('geograph/gazetteer.class.php');

init_session();


$smarty = new GeographPage;

$template='map_frame.tpl';
$cacheid='';


$square=new GridSquare;


$token=new Token;
if ($token->parse($_GET['t']))
{
	$s = false;
	$rastermap = new RasterMap($s);
	foreach ($token->data as $key => $value) {
		$rastermap->{$key} = $value;
	
	}
	$rastermap->inline=true;
	
	$smarty->assign_by_ref('rastermap', $rastermap);
	
} else {
	die("invalid");
}



$smarty->display($template,$cacheid);

	
?>
