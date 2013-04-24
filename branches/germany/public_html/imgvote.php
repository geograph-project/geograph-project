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

if (!$USER->hasPerm("basic")) {
	trigger_error("voting without logging in", E_USER_WARNING);
	print "-1:not logged in";
	exit;
}

$types = array('info', 'qual', 'site', 'like');

if (  !isset($_POST['imageid'])
    ||!preg_match('/^\s*[1-9][0-9]*\s*$/', $_POST['imageid'])) {
	trigger_error("invalid parameters: {$_POST['imageid']} {$_REQUEST['vote']} {$_REQUEST['type']}", E_USER_WARNING);
	print "-3:invalid parameters";
	exit;
}

if (!isset($_POST['vote']) && !isset($_POST['type'])) {
	# only get current votes for this image
	$vote = 0;
	$type = '';
} elseif (  !isset($_POST['vote'])
          ||!preg_match('/^\s*[1-5]\s*$/', $_POST['vote'])
          ||!isset($_POST['type'])
          ||!in_array($_POST['type'], $types, true)) {
	trigger_error("invalid parameters: {$_POST['imageid']} {$_REQUEST['vote']} {$_REQUEST['type']}", E_USER_WARNING);
	print "-3:invalid parameters";
	exit;
} else {
	# vote and get current votes for this image
	$vote = intval($_POST['vote']);
	$type = $_POST['type'];
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

?>
