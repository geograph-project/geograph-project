<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');



init_session();
$template='maplarge.tpl';

$smarty = new GeographPage;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24*7; //7 day cache

//initialise mosaic
$mosaic=new GeographMapMosaic;
$overview=new GeographMapMosaic('overview');

if ($_GET['o'])
	$overview->setToken($_GET['o']);
	
if (isset($_GET['t']))
	$mosaic->setToken($_GET['t']);

if ($mosaic->pixels_per_km != 80)
	die("Invalid Parameter");

$mosaic->setMosaicSize(800,800);
$mosaic->setScale(80);

//get token, we'll use it as a cache id
$token=$mosaic->getToken();


//regenerate html?

$cacheid='maplarge|'.$token;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	//assign overview to smarty
	$overview->assignToSmarty($smarty, 'overview');
	$smarty->assign('marker', $overview->getBoundingBox($mosaic));
	
	//assign main map to smarty

	$mosaic->assignToSmarty($smarty, 'mosaic');
	
	//assign all the other useful stuff
	$gridref = $mosaic->getGridRef(-1,-1);
	$smarty->assign('gridref', $gridref);
	$smarty->assign('mapwidth', round($mosaic->image_w /$mosaic->pixels_per_km ) );
	preg_match("/([A-Z]+\d)\d(\d)\d/",$gridref,$matches);
	$smarty->assign('gridref2',$matches[1].$matches[2] );
	
	
	$left=$mosaic->map_x;
	$bottom=$mosaic->map_y;
	$right=$left + floor($mosaic->image_w/$mosaic->pixels_per_km)-1;
	$top=$bottom + floor($mosaic->image_h/$mosaic->pixels_per_km)-1;

$sql="SELECT user.user_id,realname,count(*) AS count,max(submitted) as last_date
FROM 
	gridsquare gs
	INNER JOIN gridimage gi USING(gridsquare_id)
	INNER JOIN user ON(gi.user_id=user.user_id)
WHERE 
	(x BETWEEN $left and $right) AND 
	(y BETWEEN $bottom and $top) AND
	percent_land<>0 AND
	seq_no = 1
GROUP BY user_id 
ORDER BY count DESC,last_date DESC
";
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$users=&$db->GetAll($sql);
	$smarty->assign_by_ref('users', $users);
}


$smarty->display($template, $cacheid);

	
?>
