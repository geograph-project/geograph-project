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
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');



init_session();
$template='maplarge.tpl';

$smarty = new GeographPage;

$smarty->caching = 2; // lifetime is per cache
customExpiresHeader(360,false,true);

//initialise mosaic
$mosaic=new GeographMapMosaic;
$overview=new GeographMapMosaic('overview');

if (isset($_GET['o']))
	$overview->setToken($_GET['o']);
	
if (isset($_GET['t'])) {
	$ok = $mosaic->setToken($_GET['t']);
	if (!$ok)
		die("Invalid Token");
} else {
	die("Missing Token");
}

if ($mosaic->pixels_per_km == 80) {
	$smarty->cache_lifetime = 3600*24*7; //7 day cache
} elseif ($mosaic->mosaic_factor == 4) {

} else {
	die("Invalid Parameter");
}

//get token, we'll use it as a cache id
$token=$mosaic->getToken();


//regenerate html?

$cacheid='maplarge|'.$token;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();

	//assign overview to smarty
	if ($mosaic->type_or_user > 0) {
		$overview->type_or_user = $mosaic->type_or_user;
		$profile=new GeographUser($mosaic->type_or_user);
		$smarty->assign('realname', $profile->realname);
		$smarty->assign('user_id', $mosaic->type_or_user);
	}
	
	if ($mosaic->pixels_per_km >= 40) { 
		//largeoverview
		$overview->setScale(1);
		list ($x,$y) = $mosaic->getCentre();
		$overview->setCentre($x,$y); //does call setAlignedOrigin
	}
	$overview->assignToSmarty($smarty, 'overview');
	$smarty->assign('marker', $overview->getBoundingBox($mosaic));
	
	//assign main map to smarty

	$mosaic->assignToSmarty($smarty, 'mosaic');
	
	//assign all the other useful stuff
	$gridref = $mosaic->getGridRef(-1,-1);
	$smarty->assign('gridref', $gridref);
	$smarty->assign('mapwidth', round($mosaic->image_w /$mosaic->pixels_per_km ) );
	

	preg_match("/([A-Z]+)(\d)5(\d)5$/",$gridref,$matches);
	$smarty->assign('hectad',$matches[1].$matches[2].$matches[3] );
	$smarty->assign('myriad',$matches[1] );

	if ($mosaic->pixels_per_km >= 40) {
		$left=$mosaic->map_x;
		$bottom=$mosaic->map_y;
		$right=$left + floor($mosaic->image_w/$mosaic->pixels_per_km)-1;
		$top=$bottom + floor($mosaic->image_h/$mosaic->pixels_per_km)-1;

		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

$sql="SELECT user_id,credit_realname,realname,
COUNT(*) AS count,
DATE_FORMAT(MAX(submitted),'%D %b %Y') as last_date,
count(distinct imagetaken) as days,
count(distinct imageclass) as categories
FROM 
	gridimage_search
WHERE 
	CONTAINS(GeomFromText($rectangle),point_xy) AND
	moderation_status = 'geograph' AND
	ftf = 1
GROUP BY user_id,realname
ORDER BY count DESC,last_date DESC
";
		$db = GeographDatabaseConnection(true);
		if (!$db) die('Database connection failed');  

		$users=&$db->GetAll($sql);
		$smarty->assign_by_ref('users', $users);
		
		
		$hectads=&$db->GetAll("select hectad,largemap_token,last_submitted from hectad_stat where x between {$mosaic->map_x}-20 and {$mosaic->map_x}+20 and y between {$mosaic->map_y}-20 and {$mosaic->map_y}+20 and largemap_token != '' order by y desc,x");
		$smarty->assign_by_ref('hectads', $hectads);
		
	}
}


$smarty->display($template, $cacheid);



