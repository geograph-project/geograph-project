<?php
/**
 * $Project: GeoGraph $
 * $Id: view.php 5653 2009-08-10 18:43:17Z hansjorg $
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
require_once('geograph/gridimage.class.php');

init_session();

$types = array('like', 'site', 'qual', 'info');

if (isset($_POST['imageid'])) {

	if (!$USER->hasPerm("basic")) {
		trigger_error("voting without logging in", E_USER_WARNING);
		print "-1:not logged in";
		exit;
	}

	if (!preg_match('/^\s*[1-9][0-9]*\s*$/', $_POST['imageid'])) {
		trigger_error("invalid parameters: {$_POST['imageid']} {$_REQUEST['vote']} {$_REQUEST['type']}", E_USER_WARNING);
		print "-3:invalid parameters";
		exit;
	}

	if (!isset($_POST['vote']) && !isset($_POST['type'])) {
		# only get current votes for this image
		$vote = 0;
		$type = '';
	} elseif (  isset($_POST['vote'])
		  &&preg_match('/^\s*[1-5]\s*$/', $_POST['vote'])
		  &&isset($_POST['type'])
		  &&in_array($_POST['type'], $types, true)) {
		# vote and get current votes for this image
		$vote = intval($_POST['vote']);
		$type = $_POST['type'];
	} else {
		trigger_error("invalid parameters: {$_POST['imageid']} {$_REQUEST['vote']} {$_REQUEST['type']}", E_USER_WARNING);
		print "-3:invalid parameters";
		exit;
	}
	$gridimage_id = intval($_POST['imageid']);

	$image = new GridImage($gridimage_id);
	if (!$image->isValid()) {
		trigger_error("invalid image id: $gridimage_id", E_USER_WARNING);
		print "-5:invalid image id";
		exit;
	}

	$uid = $USER->user_id;

	if ($vote && !$image->vote($uid, $type, $vote)) {
		print "-7:update failed";
		exit;
	}

	$votes =& $image->getVotes($uid);
	if ($votes === false) {
		print "-8:could not retrieve votes";
		exit;
	}

	$retval = '0';

	foreach ($votes as $curtype=>$curval) {
		$retval .= ':'.$curtype.':'.$curval;
	}

	print $retval;
	exit;
}

require_once('geograph/imagelist.class.php');
#FIXME caching
#FIXME admin: also show $uid
$USER->mustHavePerm("basic");

/* let admin have a look at another user's stats */
if (   isset($_GET['u'])
    && preg_match('/^\s*[1-9][0-9]*\s*$/', $_GET['u'])
    && $USER->hasPerm("admin")) {
	$uid = intval($_GET['u']);
} else {
	$uid = $USER->user_id;
}

/* show votes on that user's images */
if (   isset($_GET['user'])
    && preg_match('/^\s*[1-9][0-9]*\s*$/', $_GET['user'])) {
	$userimg = intval($_GET['user']);
} else {
	$userimg = 0;
}

if (   isset($_GET['vote'])
    && preg_match('/^\s*[1-5]\s*$/', $_GET['vote'])) {
	$vote = intval($_GET['vote']);
} else {
	$vote = 0;
}

if (   isset($_GET['type'])
    && in_array($_GET['type'], $types, true)) {
	$type = $_GET['type'];
} else {
	$type = '';
}

$limit = 20;

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

$smarty = new GeographPage;

$typenames = array('like' => 'General impression', 'site' => 'Location', 'qual' => 'Image quality', 'info' => 'Geographical information');

$smarty->assign('type', $type);
$smarty->assign('vote', $vote);
$smarty->assign('userimg', $userimg);
if ($userimg) {
	$profile = new GeographUser($userimg);
	$smarty->assign('realname', $profile->realname);
}
$smarty->assign('uservote', $uid);
$smarty->assign_by_ref('types', $types);
$smarty->assign_by_ref('typenames', $typenames);

// use user_vote_stat?
$sql = "SELECT gv.type, COUNT(*), SUM(gv.vote=1), SUM(gv.vote=2), SUM(gv.vote=3), SUM(gv.vote=4), SUM(gv.vote=5)".
	"FROM gridimage gi INNER JOIN gridimage_vote gv ON (gi.gridimage_id=gv.gridimage_id)".
	"WHERE gv.user_id=$uid AND gi.user_id != gv.user_id GROUP BY gv.type;";

$votestat =& $db->GetAssoc($sql);
$smarty->assign_by_ref('votestat', $votestat);

$where = "gv.user_id=$uid ";
if ($userimg) {
	$where .= "and gi.user_id=$userimg ";
}
if ($vote) {
	$where .= "and gv.vote=$vote ";
}
if ($type === '') {
	$group = "group by gv.gridimage_id ";
	$time = "MAX(gv.created)";
} else {
	$group = "";
	$time = "gv.created";
	$where .= "and gv.type='$type' ";
}

$sql = "select gi.*,grid_reference,$time as vtime,u.realname as contributorname ".
	"from gridimage as gi ".
	"inner join gridsquare as gs using(gridsquare_id) ".
	"inner join gridimage_vote as gv using(gridimage_id) ".
	"inner join user as u on (gi.user_id=u.user_id) ".
	"where moderation_status in ('geograph','accepted') and ".
	$where.$group.
	"order by vtime desc limit $limit;";

$imagelist=new ImageList;
$imagelist->_getImagesBySql($sql);

if (count($imagelist->images)) {
	foreach ($imagelist->images as &$image) {
		$image->imagetakenString = getFormattedDate($image->imagetaken);
		$image->votes =& $image->getVotes($uid);
	}
	unset($image);
	$smarty->assign_by_ref('images', $imagelist->images);
}

$template='imgvote.tpl';

$cacheid=0;

$smarty->display($template, $cacheid);

?>
