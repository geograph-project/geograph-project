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

$hectad = (isset($_GET['hectad']) && preg_match('/^\w{1,3}\s*\d{2}$/',$_GET['hectad']))?$_GET['hectad']:'';

if (empty($hectad)) {
	$db = GeographDatabaseConnection(true);
	
	$hectad = $db->getOne("select hectad from hectad_stat where landsquares > 0 order by rand()");
}

$cacheid = $hectad;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	if (!$db) {
		$db = GeographDatabaseConnection(true);
	}
	
	$row = $db->getRow("select * from hectad_stat where hectad = ".$db->Quote($hectad));
	
	if (empty($row)) {
		header("Location: /browse.php?gridref=".urlencode($hectad));
		exit;
	} 

	if (empty($row['map_token'])) {
		require_once('geograph/mapmosaic.class.php');

		$mosaic=new GeographMapMosaic;
		$mosaic->setScale(40);
		$mosaic->setMosaicFactor(2);

		$ri = $row['reference_index'];
		$x = ( intval(($row['x'] - $CONF['origins'][$ri][0])/10)*10 ) +  $CONF['origins'][$ri][0];
		$y = ( intval(($row['y'] - $CONF['origins'][$ri][1])/10)*10 ) +  $CONF['origins'][$ri][1];

		//get a token to show a suroudding geograph map
		$mosaic->setOrigin($x,$y);

		$row['map_token'] = $mosaic->getToken();

		$db = GeographDatabaseConnection(false);

		$db->Execute(sprintf("UPDATE hectad_stat SET
			map_token = %s
			WHERE hectad = %s",
			$db->Quote($row['map_token']),
			$db->Quote($row['hectad']) ));
	}


	$smarty->assign_by_ref('row',$row);

	$smarty->assign('hectad',$hectad);
}


$smarty->display($template, $cacheid);

	
?>
