<?php
/**
 * $Project: GeoGraph $
 * $Id: maplarge.php 6419 2010-03-05 11:50:59Z barry $
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


$hectad = (isset($_GET['hectad']) && ctype_alnum($_GET['hectad']))?$_GET['hectad']:'';

$hectad_assignment_id = (isset($_GET['id']))?intval($_GET['id']):0;


$db = GeographDatabaseConnection(true);

$assignment = $db->getRow("SELECT ha.*,realname FROM hectad_assignment ha INNER JOIN user USING (user_id) WHERE status = 'accepted' AND hectad = '$hectad' AND hectad_assignment_id = $hectad_assignment_id LIMIT 1");


$row = $db->getRow("select * from hectad_stat where hectad = ".$db->Quote($hectad));

if (empty($row) || empty($assignment)) {
	#header("Location: /browse.php?gridref=".urlencode($hectad));
	header("HTTP/1.0 404 Not Found");
	$smarty->display("static_404.tpl");
	exit;
} else {

	$mosaic->setScale(80);
	$mosaic->setMosaicFactor(2);
	$mosaic->setMosaicSize(800,800);

	$ri = $row['reference_index'];
	$x = ( intval(($row['x'] - $CONF['origins'][$ri][0])/10)*10 ) +  $CONF['origins'][$ri][0];
	$y = ( intval(($row['y'] - $CONF['origins'][$ri][1])/10)*10 ) +  $CONF['origins'][$ri][1];

	//get a token to show a suroudding geograph map
	$mosaic->setOrigin($x,$y);
	
	$mosaic->adopt = true;
	$mosaic->type_or_user = -20;
}


//get token, we'll use it as a cache id
$token=$mosaic->getToken();


//regenerate html?

$cacheid='maplarge|'.$token;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();

	$smarty->assign('realname', $assignment['realname']);
	$smarty->assign('user_id', $assignment['user_id']);

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
	
	//remove this for now - it doesnt work yet //TODO!
	$smarty->assign('mosaic_token','0');
	
	//assign all the other useful stuff
	$gridref = $mosaic->getGridRef(-1,-1);
	$smarty->assign('gridref', $gridref);
	$smarty->assign('mapwidth', round($mosaic->image_w /$mosaic->pixels_per_km ) );
	

	preg_match("/([A-Z]+)(\d)5(\d)5$/",$gridref,$matches);
	$smarty->assign('hectad',$matches[1].$matches[2].$matches[3] );
	$smarty->assign('myriad',$matches[1] );


}


$smarty->display($template, $cacheid);



