<?php
/**
 * $Project: GeoGraph $
 * $Id: myriad.php 5786 2009-09-12 10:18:04Z barry $
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

$template='hectad.tpl';

$hectad = (isset($_GET['hectad']) && preg_match('/^\w{1,3}\s*\d{2}$/',$_GET['hectad']))?strtoupper($_GET['hectad']):'';

if (empty($hectad)) {
	$db=NewADOConnection($GLOBALS['DSN']);
	
	$hectad = $db->getOne("select hectad from hectad_stat where landsquares > 0 order by rand()");
}

$cacheid = $hectad;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	if (!$db) {
		$db=NewADOConnection($GLOBALS['DSN']);
	}
	
	$row = $db->getRow("select * from hectad_stat where hectad = ".$db->Quote($hectad));
	
	if (empty($row)) {
		#header("Location: /browse.php?gridref=".urlencode($hectad));
		header("HTTP/1.0 404 Not Found");
		$smarty->display("static_404.tpl");
		exit;
	} 

	$data = $db->GetRow("SHOW TABLE STATUS LIKE 'hectad_stat'");
	$smarty->assign('updated',$data['Update_time']);
	
	require_once('geograph/mapmosaic.class.php');
	$mosaic=new GeographMapMosaic;
	$overview=new GeographMapMosaic('largeoverview');
	$overview->setCentre($row['x'],$row['y']);
	
	$overview2=new GeographMapMosaic('overview');

	if (empty($row['map_token'])) {
		
		$mosaic->setScale(40);
		$mosaic->setMosaicFactor(2);

		$ri = $row['reference_index'];
		$x = ( intval(($row['x'] - $CONF['origins'][$ri][0])/10)*10 ) +  $CONF['origins'][$ri][0];
		$y = ( intval(($row['y'] - $CONF['origins'][$ri][1])/10)*10 ) +  $CONF['origins'][$ri][1];

		//get a token to show a suroudding geograph map
		$mosaic->setOrigin($x,$y);

		$row['map_token'] = $mosaic->getToken();

		$db=NewADOConnection($GLOBALS['DSN']);

		$db->Execute(sprintf("UPDATE hectad_stat SET
			map_token = %s
			WHERE hectad = %s",
			$db->Quote($row['map_token']),
			$db->Quote($row['hectad']) ));
	} else {
		$mosaic->setToken($row['map_token']);
	}

	$overview->assignToSmarty($smarty, 'overview');
	$smarty->assign('marker', $overview->getBoundingBox($mosaic));
	
	$overview2->assignToSmarty($smarty, 'overview2');
	$smarty->assign('marker2', $overview2->getBoundingBox($mosaic));

			
	$hectads=&$db->GetAll("select hectad,last_submitted,(geosquares>=landsquares) as completed from hectad_stat where x between {$row['x']}-15 and {$row['x']}+15 and y between {$row['y']}-15 and {$row['y']}+15 order by y desc,x");
	$smarty->assign_by_ref('hectads', $hectads);


	$smarty->assign($row);

	$smarty->assign('myriad',preg_replace('/\d+/','',$hectad));
}


$smarty->display($template, $cacheid);

	
?>
