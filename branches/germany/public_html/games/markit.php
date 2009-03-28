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

if (isset($_REQUEST['t'])) {
	$ok=false;
	$token=new Token;

	if ($token->parse($_REQUEST['t']))
	{
		if ($token->hasValue("i")) {
			$i = $token->getValue("i");
		}
		if ($token->hasValue("p")) {
			$_GET['page'] = $token->getValue("p");
		}
	}
} 
if (isset($_REQUEST['debug']) && $USER->hasPerm("admin")) {
	$token=new Token;

	$token->setValue("i", $i);
	if (!empty($_GET['page'])){ 
		$token->setValue("p", $_GET['page']);
	}
	print $token->getToken(); 
}
if (isset($_GET['l']) && isset($_SESSION['gameToken'])) {
	unset($_SESSION['gameToken']);
}

$game = new Game();

if (isset($_REQUEST['token'])) {
	$game->setToken($_REQUEST['token']);
} elseif (isset($_SESSION['gameToken'])) {
	$game->setToken($_SESSION['gameToken']);
} 

if (isset($_REQUEST['debug']) && $USER->hasPerm("admin") && $game->i) {
	print "<br>{$game->i}";
}

if (isset($_REQUEST['autoload'])) {
	switch (rand(1,3)) {
		case 1: $i = 958023; $_GET['page'] = 'x'; break;
		case 2: $l = rand(1,5); break;
		case 3: $l = rand(2,4)*10; break;
	}
}

$game->game_id = 1;
$game->batchsize = 10;

if (isset($_GET['check'])) {
	if (empty($_GET['grid_reference'])) {
		die('<span style="color:red">Drag the Icon from under the map to mark photo subject.</span>');
	} elseif (empty($_GET['points'])) {
		$game->image->use6fig = false;
		die('<span style="background-color:red; color:white">Oh dear, no tokens left!</span>^set:'.$game->image->getSubjectGridref(false));
	} else {
		$square=new GridSquare;
		$grid_ok=$square->setByFullGridRef($_GET['grid_reference'],true);

		$distance = sprintf("%0.2f",
			sqrt(pow($game->image->nateastings-$square->nateastings,2)+pow($game->image->natnorthings-$square->natnorthings,2)));

		if ($_GET['points'] == 1) {
			$postfix = "But no tokens left! Better luck next time.";
			$postfix2 = "^-1^set:".$game->image->getSubjectGridref(false);
		} else {
			$postfix = "try again...";
			$postfix2 = "^-1";
		}
		$prefix = "<span style=\"color:blue;background-color:pink; padding:10px;\">";
		$postfix .= "</span>".$postfix2;
		if ($distance < 100) {
			$game->image->use6fig = false;
			echo "<span style=\"color:blue;background-color:lightgreen; padding:10px; font-weight:bold;\">Well done, you were within 100m, collect {$_GET['points']} tokens</span>^1^set:".$game->image->getSubjectGridref(false);
			exit;
		} elseif ($distance < 200) {
			$prefix = str_replace('pink','yellow',$prefix);
			switch(rand(1,3)) {
				case '1' : echo $prefix."Close, within 200m!... $postfix"; break;
				case '2' : echo $prefix."Somewhere between 100m and 200m... $postfix"; break;
				case '3' : echo $prefix."Almost but not quite, only another 100m to go... $postfix"; break;
			}
			exit;
		} elseif ($distance < 500) {
			$prefix = str_replace('pink','orange',$prefix);
			switch(rand(1,3)) {
				case '1' : echo $prefix."Within 500m, but could do better... $postfix"; break;
				case '2' : echo $prefix."Somewhere between 200m and 500m... $postfix"; break;
				case '3' : echo $prefix."Couldn't do better than 200m, huh?... $postfix"; break;
			}
			exit;
		} else {
			switch(rand(1,3)) {
				case '1' : echo $prefix."Not even within 500m... $postfix"; break;
				case '2' : echo $prefix."Sorry, over 500m away... $postfix"; break;
				case '3' : echo $prefix."Couldn't do better than 500m, huh?... $postfix"; break;
			}
			exit;
		}		

		echo $distance;
		exit;
	}
	
	die('unknown error');

} elseif (isset($_REQUEST['next']) || isset($_REQUEST['save'])) {
	
	if (empty($_REQUEST['grid_reference'])) {

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
	#$params[] = "token=".$game->getToken();
	$_SESSION['gameToken'] = $game->getToken();
	
	if (isset($_REQUEST['next'])) {
		header("Location: /games/markit.php?".implode('&',$params));
	} else {
		header("Location: /games/score.php?".implode('&',$params));
	}
	exit;
}


if ($i) {
	if (!empty($_GET['page'])){ 
		$game->setSearchPage($i,$_GET['page']);
	}

	$game->getImagesBySearch($i);
	$game->i = $i;
	
	if ($game->numberofimages > 0) {
		$game->setSearchPage($i,'x');
		$game->getImagesBySearch($i);
	}
	
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
	$index = 0;
	$keys = array_keys($game->images);
	while (count($keys)) {
		$game->useImage($keys[0],true,true);
		$index++;
		if (!empty($game->rastermap) && $game->rastermap->enabled && $game->image->gridimage_id) {
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

$smarty->assign_by_ref('game',$game);

$smarty->display('games_markit.tpl');

?>
