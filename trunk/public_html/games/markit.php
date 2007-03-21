<?php
/**
 * $Project: GeoGraph $
 * $Id: search.php 2403 2006-08-16 15:55:41Z barry $
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/searchcriteria.class.php');
init_session();




$rater=inEmptyRequestInt('rater',0);
$i=inEmptyRequestInt('i',0);
$l=inEmptyRequestInt('l',0);


$game = new Game();

if (isset($_REQUEST['token'])) {
	$game->setToken($_REQUEST['token']);
}

$game->game_id = 1;

if (isset($_GET['check'])) {
	if (empty($_GET['grid_reference'])) {
		die('Drag the Icon from user the map to mark photo subject.');
	} elseif (empty($_GET['points'])) {
		die('Oh dear, no points left!^set:'.$game->image->getSubjectGridref(true));
	} else {
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($_GET['grid_reference'],true);

		$distance = sprintf("%0.2f",
			sqrt(pow($game->image->nateastings-$square->nateastings,2)+pow($game->image->natnorthings-$square->natnorthings,2)));

		if ($_GET['points'] == 1) {
			$postfix = "But no pineapples left! Better luck next time.";
		} else {
			$postfix = "try again...";
		}

		if ($distance < 100) {
			echo "Well done, you where within 100m, collect {$_GET['points']} pineapples^1";
			exit;
		} elseif ($distance < 200) {
			echo "Close, within 200m... $postfix^-1";
			exit;
		} elseif ($distance < 500) {
			echo "Well... within 500m... $postfix^-1";
			exit;
		} else {
			echo "Not even within 500m... $postfix^-1";
			exit;
		}		

		echo $distance;
		exit;
	}
	
	die('unknown error');

} elseif (isset($_REQUEST['next']) || isset($_REQUEST['save'])) {
	
	if (empty($_REQUEST['grid_reference'])) {
		die('Drag the Icon from user the map to mark photo subject.');
	} else {
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($_REQUEST['grid_reference'],true);

		$distance = sprintf("%0.2f",
			sqrt(pow($game->image->nateastings-$square->nateastings,2)+pow($game->image->natnorthings-$square->natnorthings,2)));

		if (!empty($_REQUEST['points']) && $distance < 100) {
			$game->storeScore(intval($_REQUEST['points']));
		} else {
			$game->storeScore(0);
		}
	}

	$params = array();
	$params[] = "token=".$game->getToken();
	if (isset($_REQUEST['rater'])) {
		$params[] = "rater=1";
		$game->saveRate($_REQUEST['rate']);		
	}
	
	if (isset($_REQUEST['next'])) {
		header("Location: /games/markit.php?".implode('&',$params));
	} else {
		header("Location: /games/score.php?".implode('&',$params));
	}
	exit;
}


if ($i) {
	$game->getImagesBySearch($i);
	$game->i = $i;
} elseif (!empty($game->i)) {
	$game->getImagesBySearch($game->i);
} elseif ($l) {
	$game->getImagesByRating($l);
	$game->l = $l;
} elseif (!empty($game->l)) {
	$game->getImagesByRating($game->l);
} else {

	die('no images');
}


$smarty = new GeographPage;


if ($game->numberofimages > 0) {
	
	$game->useImage(0,true,true);
	
	
	$game->points = 5;
	
	if ( $USER->hasPerm("basic") && isset($_REQUEST['rater'])) {
		$smarty->assign('rater',1);
	}

} else {
	die('no images left');
}

$smarty->assign_by_ref('game',$game);

$smarty->display('games_markit.tpl');

?>
