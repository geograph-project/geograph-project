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


$smarty = new GeographPage;

if (isset($_REQUEST['login']) && !$USER->hasPerm('basic')) {
	$USER->login(false);
}

$game = new Game();

if (isset($_SESSION['gameToken'])) {
	$game->setToken($_SESSION['gameToken']);
} elseif (isset($_REQUEST['token'])) {
	$game->setToken($_REQUEST['token']);
}

if (isset($_REQUEST['debug']) && $USER->hasPerm('admin')) {
	print_r($game);
}

if (!empty($game->image)) {
	unset($game->image);
}
if (!empty($game->rastermap)) {
	unset($game->rastermap);
}

if (!empty($_REQUEST['save']) && ($USER->registered || !empty($_REQUEST['username'])) ) {
	if (!empty($_REQUEST['username']) && !isValidRealName($_REQUEST['username'])) {
		$smarty->assign('errormsg',"Please only use only letters and numbers in your name, in particular you should not enter an email address");
	} else {
		$app = $game->saveScore($_REQUEST['save'],!empty($_REQUEST['username'])?$_REQUEST['username']:'');
		if (isset($_SESSION['gameToken'])) {
			unset($_SESSION['gameToken']);
		}
		if (!empty($_REQUEST['username'])) {
			$_SESSION['username']= $_REQUEST['username'];
		} 
		if ($app) {
			header("Location: /games/moversboard.php?g={$game->game_id}&more");
		} else {
			header("Location: /games/");
		}
		exit;
	}
}

$smarty->assign_by_ref('game',$game);
if (!empty($_SESSION['username'])) {
	$smarty->assign('username',$_SESSION['username']);
}
$smarty->display('games_score.tpl');

?>
