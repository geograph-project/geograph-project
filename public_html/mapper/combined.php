<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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
init_session();

pageMustBeHTTPS();

$smarty = new GeographPage;

if ($CONF['template']!='ireland' && empty($_GET['mobile'])) {
	$G = $_GET;
	$G['lang'] = 'cy';
        $smarty->assign('welsh_url',"/mapper/combined.php?".http_build_query($G)); //needed by the english template!
	unset($G['lang']);
        $smarty->assign('english_url',"/mapper/combined.php?".http_build_query($G)); //needed by the welsh template!
}


if (!empty($_GET['user_id'])) {
        $profile=new GeographUser(intval($_GET['user_id']));

        if (empty($profile->stats)) {
                $profile->getStats();
        }

	if (!empty($profile->stats['images'])) {
		$smarty->assign('realname',$profile->realname);
		$smarty->assign('stats',$profile->stats);
		$smarty->assign('filter',1);
	}

} elseif ($USER->registered) {
	$USER->getStats();
	$smarty->assign('stats',$USER->stats);
	$smarty->assign('ownfilter',1);
	if (!empty($_GET['mine'])) {
		$smarty->assign('filter',1);
	}
}


if (isset($_GET['t'])) {
	require_once('geograph/mapmosaic.class.php');
	$mosaic=new GeographMapMosaic;

        if ($mosaic->setToken($_GET['t'])) {
		$gridref = $mosaic->getGridRef(-1,-1);

	        $smarty->assign('gridref',$gridref);
		$smarty->assign('zoom', 12);
	}

} elseif (!empty($_SESSION['gridref'])) {
        $smarty->assign('gridref',$_SESSION['gridref']);
}


if (!empty($_GET['dots'])) {
        $smarty->assign('dots',1);
}
if (!empty($_GET['views'])) {
        $smarty->assign('views',1);
}

$smarty->assign('g_time',filemtime("../guider/mapper_guider.js"));

if (!empty($_GET['dev'])) {
	$smarty->display('mapper_combined_dev.tpl');

} elseif (!empty($_GET['mobile'])) {
	$smarty->display('mapper_combined_mobile.tpl');
} else {
	$smarty->display('mapper_combined.tpl');
}
