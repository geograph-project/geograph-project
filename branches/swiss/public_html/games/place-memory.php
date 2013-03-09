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
$l=inEmptyRequestInt('l',0);

if (isset($_GET['l']) && isset($_SESSION['gameToken'])) {
	unset($_SESSION['gameToken']);
}

$game = new Game();

if (isset($_REQUEST['token'])) {
	$ok = $game->setToken($_REQUEST['token']);
} elseif (isset($_SESSION['gameToken'])) {
	$ok = $game->setToken($_SESSION['gameToken']);
} 


$game->game_id = 2;
$game->batchsize = 10;

if (isset($_GET['check'])) {
	if (empty($ok) || !$ok) {
		die("Game Expired, please start again");
	}
	if (empty($_GET['grid_reference'])) {
		die('<span style="color:red">Please enter a Grid Reference</span>');
	} elseif (empty($_GET['points'])) {
		$game->image->use6fig = false;
		die('<span style="background-color:red; color:white">Oh dear, no tokens left!</span>^set:'.$game->image->getSubjectGridref(false));
	} else {
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($_GET['grid_reference'],true);

		$match = $game->image->grid_reference == $square->grid_reference;
		
		if (!$match) {
			$distance = sprintf("%d",
				sqrt(pow($game->image->grid_square->x-$square->x,2)+pow($game->image->grid_square->y-$square->y,2)));
		}
		
		if ($_GET['points'] == 1) {
			$postfix = "But no tokens left! Better luck next time.";
			$postfix2 = "^-1^set:".$game->image->grid_reference;
		} else {
			$postfix = "try again...";
			$postfix2 = "^-1";
		}
		$prefix = "<span style=\"color:blue;background-color:pink; padding:10px;\">";
		$postfix .= "</span>".$postfix2;
		if ($match) {
			$game->image->use6fig = false;
			echo "<span style=\"color:blue;background-color:lightgreen; padding:10px; font-weight:bold;\">Well done, got the square! collect {$_GET['points']} tokens</span>^1^set:".$game->image->grid_reference;
			exit;
		} else {
			$_SESSION['thisGamePoints'] = max(0,$_GET['points']-1);
			$_SESSION['thisGameImageID'] = $game->image->gridimage_id;
			if ($distance < 10) {
				echo $prefix."Not Quite, distance: about {$distance}km ... $postfix";
			} else {
				echo $prefix."Not Right, distance: about {$distance}km ... $postfix";
			}
			exit;
		}

		echo $distance;
		exit;
	}
	
	die('unknown error');

} elseif (isset($_GET['map'])) {
	if (empty($ok) || !$ok) {
		die("Game Expired, please start again");
	}
	if (!$game->l) {
		die("Game Expired, please start again");
	}
	$square=new GridSquare;
	$grid_ok=$square->setByFullGridRef($_GET['grid_reference'],true);

	if ($grid_ok && $square->grid_reference) {
		$rastermap = new RasterMap($square,false,$square->natspecified);
		
		print $rastermap->getImageTag();
		print '<br/>Map for <b>'.htmlspecialchars($_GET['grid_reference']).'</b>';
		exit;	
	} else {
		die('<span style="color:red">Please enter a Grid Reference</span>');
	}
} elseif (isset($_REQUEST['next']) || isset($_REQUEST['save'])) {
	if (empty($ok) || !$ok) {
		die("Game Expired, please start again");
	}
	if (empty($_REQUEST['grid_reference'])) {

	} else {
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($_REQUEST['grid_reference'],true);

		$match = $game->image->grid_reference == $square->grid_reference;
		
		if (!empty($_REQUEST['points']) && $match) {
			$game->storeScore(intval($_REQUEST['points']));
		} else {
			$game->storeScore(0);
		}
	}

	$params = array();
	if (isset($_REQUEST['rater'])) {
		$params[] = "rater=1";
		if (!empty($_REQUEST['rate'])) {
			$game->saveRate($_REQUEST['rate']);
			if ($_REQUEST['rate'] < 0) {
				if (isset($game->image->gridimage_id)) {
					$game->done[] = $game->image->gridimage_id;

				}
			}
		}
	}
	#$params[] = "token=".$game->getToken(3600);
	$_SESSION['gameToken'] = $game->getToken(3600);
	
	if (isset($_SESSION['thisGamePoints'])) {
		unset($_SESSION['thisGamePoints']);
	}
	
	if (isset($_REQUEST['next'])) {
		header("Location: /games/place-memory.php?".implode('&',$params));
	} else {
		header("Location: /games/score.php?".implode('&',$params));
	}
	exit;
} elseif (!empty($_GET['grid_reference']) && preg_match('/^[\w ]+$/',$_GET['grid_reference'])) {
	$game->grid_reference = $_GET['grid_reference'];
}

if ($l) {
	$game->getImagesByLevel($l,1);
	$game->l = $l;
} elseif (!empty($game->l)) {
	$game->getImagesByLevel($game->l,1);
} else {

	die('no images');
}


$smarty = new GeographPage;


if ($game->numberofimages > 0) {
	$index = 0;
	$keys = array_keys($game->images);
	while (count($keys)) {
		$game->useImage($keys[0]);
		$index++;
		if ($game->image->gridimage_id) {
			//it worked!
			break;
		} 
		if (!empty($game->image)) {
			unset($game->image);
		}
		if (!empty($game->rastermap)) {
			unset($game->rastermap);
		}
		$keys = array_keys($game->images);
	}
	
	$game->points = 5;
	
	if ( $USER->hasPerm("basic") && isset($_REQUEST['rater'])) {
		$smarty->assign('rater',1);
	}

} else {
	$smarty->assign('message','no images left');
	if (!empty($game->image)) {
		unset($game->image);
	}
	if (!empty($game->rastermap)) {
		unset($game->rastermap);
	}
}

$smarty->assign('gameToken',$game->getToken(3600));

if (!empty($_SESSION['thisGamePoints']) && $_SESSION['thisGameImageID'] == $game->image->gridimage_id) {
	$game->points = $_SESSION['thisGamePoints'];
}

$smarty->assign_by_ref('game',$game);

$smarty->display('games_place-memory.tpl');

?>
