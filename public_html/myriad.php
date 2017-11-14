<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

customGZipHandlerStart();

$template='myriad.tpl';

$myriad = (isset($_GET['myriad']) && ctype_upper($_GET['myriad']))?$_GET['myriad']:'';

if (empty($myriad)) {
	$db=NewADOConnection($GLOBALS['DSN']);
	
	$myriad = $db->getOne("select prefix from gridprefix where landcount > 0 order by rand()");
}

$cacheid = $myriad;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/map.class.php');
	require_once('geograph/mapmosaic.class.php');

	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
	}
	
	$prefix = $db->getRow("select * from gridprefix where prefix= '$myriad'");

	$mosaic=new GeographMapMosaic;	
	//start with same params
	$mosaic->setScale(4);
	$mosaic->setMosaicFactor(2);
	$mosaic->setOrigin($prefix['origin_x'],$prefix['origin_y']); 
	$mosaic->type_or_user = -1;
	$smarty->assign('token',$mosaic->getToken());
	
	
	$smarty->assign('myriad',$myriad);
}


$smarty->display($template, $cacheid);

	
?>
