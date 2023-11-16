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
	header("Location: /hectadmap.php");
	exit;

	$db = GeographDatabaseConnection(true);
	$hectad = $db->getOne("select hectad from hectad_stat where landsquares > 0 order by rand()");
}

$cacheid = $hectad;

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	if (empty($db)) {
		$db = GeographDatabaseConnection(true);
	}

	$row = $db->getRow("select * from hectad_stat where hectad = ".$db->Quote($hectad));

	if (empty($row)) {
		#header("Location: /browse.php?gridref=".urlencode($hectad));
		header("HTTP/1.0 404 Not Found");
		$smarty->display("static_404.tpl");
		exit;
	}

	pageMustBeHTTPS();

	$data = $db->GetRow("SHOW TABLE STATUS LIKE 'hectad_stat'");
	$smarty->assign('updated',$data['Update_time']);

        $smarty->assign('extra_meta', "<link rel=\"canonical\" href=\"{$CONF['canonical_domain'][$row['reference_index']]}/gridref/{$row['hectad']}\"/>");

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

		$db = GeographDatabaseConnection(false);

		$db->Execute(sprintf("UPDATE hectad_stat SET
			map_token = %s
			WHERE hectad = %s",
			$db->Quote($row['map_token']),
			$db->Quote($row['hectad']) ));
	} else {
		$mosaic->setToken($row['map_token']);
	}


        list ($x,$y) = $mosaic->getCentre();
        $conv = new Conversions;
        list($lat,$long) = $conv->internal_to_wgs84($x,$y,$row['reference_index']);
        $smarty->assign('lat', $lat);
        $smarty->assign('long', $long);


	$overview->assignToSmarty($smarty, 'overview');
	$smarty->assign('marker', $overview->getBoundingBox($mosaic));

	$overview2->assignToSmarty($smarty, 'overview2');
	$smarty->assign('marker2', $overview2->getBoundingBox($mosaic));


	$hectads=$db->GetAll("select hectad,last_submitted,(geosquares>=landsquares) as completed from hectad_stat where x between {$row['x']}-15 and {$row['x']}+15 and y between {$row['y']}-15 and {$row['y']}+15 order by y desc,x");
	$smarty->assign_by_ref('hectads', $hectads);


	$smarty->assign($row);

	$smarty->assign('myriad',preg_replace('/\d+/','',$hectad));



        $ctx = stream_context_create(array(
            'http' => array(
                'timeout' => 3
                )
            )
        );

	//calling our own API is ugly, but better than replicating all the code here?
	ini_set("user_agent","Internal Request");
        $remote = file_get_contents("https://api.geograph.org.uk/finder/bytag.json.php?q=hectad:$hectad",0, $ctx);

        if (!empty($remote) && strlen($remote) > 110) {
		require_once '3rdparty/JSON.php';
		$tags = json_decode($remote);

		$str = $sep = '';
		$idx = 0;
		while ($idx < count($tags) && strlen($str) <180) {
			$tag = $tags[$idx];
			$str .= $sep . (($tag->prefix && $tag->prefix != 'top' && $tag->prefix != 'term' && $tag->prefix != 'bucket')?"{$tag->prefix}:":'').$tag->tag;
			$sep = ', ';
			$idx++;
		}
		$smarty->assign('meta_description', "Common Tags for $hectad: ".$str);

		$smarty->assign_by_ref('tags', $tags);
	}
}

if ($USER->registered) {
        if (empty($USER->stats))
                $USER->getStats();
	if (!empty($USER->stats))
		$smarty->assign_by_ref('stats', $USER->stats);
}

$smarty->display($template, $cacheid);

