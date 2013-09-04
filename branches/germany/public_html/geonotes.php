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
require_once('geograph/gridimagenote.class.php');
require_once('geograph/gridimagetroubleticket.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/uploadmanager.class.php');

init_session();

/*
 * Note upload: Basic check of input parameters.
 * Returns: 0 on success, error message on error
 */
function check_params($no)
{
	if ($no == 1) {
		$suffix = '';
	} else {
		$suffix = '_'.$no;
	}
	if (  !isset($_REQUEST["id$suffix"])
	    ||!isset($_REQUEST["x1$suffix"])
	    ||!isset($_REQUEST["x2$suffix"])
	    ||!isset($_REQUEST["y1$suffix"])
	    ||!isset($_REQUEST["y2$suffix"])
	    ||!isset($_REQUEST["z$suffix"])
	    ||!isset($_REQUEST["status$suffix"])
	    ||!isset($_REQUEST["imgwidth$suffix"])
	    ||!isset($_REQUEST["imgheight$suffix"])
	    ||!isset($_REQUEST["comment$suffix"])) {
		return '-4:0:missing parameters';
	}
	if (  !preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST["id$suffix"])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST["x1$suffix"])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST["y1$suffix"])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST["x2$suffix"])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST["y2$suffix"])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST["z$suffix"])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST["imgwidth$suffix"])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST["imgheight$suffix"])
	    ||$_REQUEST["status$suffix"] != 'visible' && $_REQUEST["status$suffix"] != 'deleted' && $_REQUEST["status$suffix"] != 'pending'
           ) {
		$a = "{$_REQUEST["comment$suffix"]},{$_REQUEST["id$suffix"]},{$_REQUEST["x1$suffix"]},{$_REQUEST["y1$suffix"]},{$_REQUEST["x2$suffix"]},{$_REQUEST["y2$suffix"]},{$_REQUEST["z$suffix"]},{$_REQUEST["imgwidth$suffix"]},{$_REQUEST["imgwidth$suffix"]},{$_REQUEST["imgheight$suffix"]},{$_REQUEST["status$suffix"]}";
		trigger_error("inv val: $a", E_USER_WARNING);
		return '-3:0:invalid parameters';
	}
	$note_id = intval($_REQUEST["id$suffix"]);
	if ($note_id == 0) {
		trigger_error("inv id: {$note_id}", E_USER_WARNING);
		return '-3:0:invalid parameters';
	}
	return 0;
}

/*
 * Note upload: Evaluate input parameters and try to change or create a note.
 * Returns: status message
 */
function commit_note($no, $gridimage_id, $imagewidth, $imageheight, $ismoderator, $isowner, $ticketnote, $immediate)
{
	global $USER;
	if ($no == 1) {
		$suffix = '';
	} else {
		$suffix = '_'.$no;
	}
	$note_id = intval($_REQUEST["id$suffix"]);
	$comment = $_REQUEST["comment$suffix"];
	if (!preg_match('/^[\x09\x0a\x0d\x20-\xff]*[\x21-\xff][\x09\x0a\x0d\x20-\xff]*$/', $comment)) {
		trigger_error("inv comment", E_USER_WARNING);
		return "-3:$note_id:invalid parameters";
	}
	$oldsu = mb_substitute_character();
	//if (!mb_substitute_character(0)) { // php only allows 0x0001...0xffff
	if (!mb_substitute_character(1)) {
		trigger_error("could not change substitute character", E_USER_WARNING);
	}
	$comment = mb_convert_encoding($comment, 'Windows-1252', 'UTF-8'); // store as Windows-1252 despite declaring latin1, see http://www.w3.org/TR/2009/WD-html5-20090423/infrastructure.html#character-encodings-0 or http://dev.w3.org/html5/spec/parsing.html#character-encodings-0
	mb_substitute_character($oldsu);
	//if (strpos($comment, chr(0)) !== false) {
	if (strpos($comment, chr(1)) !== false) {
		return "-2:$note_id:could not convert character";
	}
	$comment = str_replace("\r\n", "\n", $comment);
	$comment = trim($comment);
	$status = $_REQUEST["status$suffix"];
	$x1 = intval($_REQUEST["x1$suffix"]);
	$x2 = intval($_REQUEST["x2$suffix"]);
	$y1 = intval($_REQUEST["y1$suffix"]);
	$y2 = intval($_REQUEST["y2$suffix"]);
	$iw = intval($_REQUEST["imgwidth$suffix"]);
	$ih = intval($_REQUEST["imgheight$suffix"]);
	$z  = intval($_REQUEST["z$suffix"]);

	if (   $x1 < 0 || $x2 >= $iw
	    || $y1 < 0 || $y2 >= $ih
	    || $iw < $imagewidth || $ih < $imageheight
	    || $z < -10 || $z > 10
	    || ($x2 - $x1 + 1)*$imagewidth  < 8*$iw // minimal width/height in std size ("640"): 8 pixels // FIXME hard coded...
	    || ($y2 - $y1 + 1)*$imageheight < 8*$ih
	    || ($note_id < 0) && $status != 'visible'
	   ) {
		trigger_error("inv param: $x1 $x2 $iw $y1 $y2 $ih $z $note_id $status $imagewidth $imageheight", E_USER_WARNING);
		return "-3:$note_id:invalid parameters";
	}

	$ticket=new GridImageTroubleTicket();
	$ticket->setSuggester($USER->user_id,$USER->realname);
	if ($ismoderator && !$isowner)
		$ticket->setModerator($USER->user_id);
	//$ticket->setType('normal');
	$ticket->setPublic('everyone'); // FIXME?
	$ticket->setImage($gridimage_id);
	$mod = !($isowner||$ismoderator&&$immediate);

	if ($ticketnote === '' ) {
		$ticketnoteappend = '.';
	} else {
		$ticketnoteappend = ":\n$ticketnote";
	}

	$note=new GridImageNote;
	if ($note_id > 0) {
		# change existing annotation using the $ticket
		$note->loadFromId($note_id);
		if (!$note->isValid() || $note->gridimage_id != $gridimage_id) {
			return "-6:$note_id:invalid note id";
		}
		$note->applyTickets($USER->user_id);
		if ($status == 'pending' && $note->status != 'pending') {
			return "-3:$note_id:setting status to pending is not allowed";
		}
		$ticket->setNotes("Changed image annotation $note_id$ticketnoteappend");
		if (   $note->x1 != $x1
		    || $note->x2 != $x2
		    || $note->y1 != $y1
		    || $note->y2 != $y2
		    || $note->imgwidth != $iw
		    || $note->imgheight != $ih
		   ) { // this way, we can display old and new position even if only one coordinate changes
			$ticket->updateField('x1', $note->x1, $x1, $mod, $note_id, true);
			$ticket->updateField('x2', $note->x2, $x2, $mod, $note_id, true);
			$ticket->updateField('y1', $note->y1, $y1, $mod, $note_id, true);
			$ticket->updateField('y2', $note->y2, $y2, $mod, $note_id, true);
			$ticket->updateField('imgwidth', $note->imgwidth, $iw, $mod, $note_id, true);
			$ticket->updateField('imgheight', $note->imgheight, $ih, $mod, $note_id, true);
		}
		$ticket->updateField('z', $note->z, $z, $mod, $note_id);
		$ticket->updateField('comment', $note->comment, $comment, $mod, $note_id);
		$ticket->updateField('status', $note->status, $status, $mod, $note_id);
		// FIXME updateField should report errors and we should pass that to the client
		if (!count($ticket->changes)) {
			return "2:$note_id";
		}
		$reqinfo = '';
	} else {
		# add new annotation with status 'pending' and change the status to 'visible' using the $ticket
		# (this way, the user gets a notification mail and we get an "annotation history")
		$newnote_id =  $note->create($gridimage_id, $x1, $x2, $y1, $y2, $iw, $ih, $comment, $z, 'pending');
		if (!$note->isValid()) {
			return "-1:$note_id:could not create annotation";
		}
		$ticket->setNotes("Created image annotation $newnote_id$ticketnoteappend");
		$ticket->updateField('status', 'pending', 'visible', $mod, $newnote_id);
		$reqinfo = ":$newnote_id";
	}
	if ($ticket->commit() == 'closed') { // FIXME error handling...
		return "0:$note_id$reqinfo";
	} else {
		return "1:$note_id$reqinfo";
	}
}

if (isset($_POST['commit'])) {
	if (!$USER->hasPerm("basic")) { // FIXME remove?
		trigger_error("note change without logging in", E_USER_WARNING);
		print "-1:0:not logged in";
		exit;
	}

	// change (id > 0) or add (id < 0) annotation(s)
	// on change, return status + ':' + id
	// on successfull creation, return status + ':' + id + ':' + new note_id
	// on error return status + ':' + id + ':' + message
	// (id=0 for errors not related to a specific note)
	// status:
	//    -6: invalid note id
	//    -5: invalid image id
	//    -4: missung parameters
	//    -3: invalid parameters
	//    -2: could not convert character
	//    -1: internal error/access denied
	//    0:  applied changes
	//    1:  pending (awaiting moderation)
	//    2:  old values = new values, no changes made
	if (  !preg_match('/^\s*[1-9][0-9]*\s*$/', $_POST['commit'])
	    ||!isset($_REQUEST['imageid'])
	    ||!preg_match('/^\s*[1-9][0-9]*\s*$/', $_REQUEST['imageid'])
	    ||isset($_REQUEST['ticketnote'])&&!preg_match('/^[\x09\x0a\x0d\x20-\xff]*$/', $_REQUEST['ticketnote'])
	    ||isset($_REQUEST['immediate'])&&!preg_match('/^\s*[01]\s*$/', $_REQUEST['immediate'])
	   ) {
		trigger_error("inv commit: {$_POST['commit']}", E_USER_WARNING);
		print "-3:0:invalid parameters";
		exit;
	}
	$ticketnote = '';
	if (isset($_REQUEST['ticketnote'])) {
		$ticketnote = $_REQUEST['ticketnote'];
		$oldsu = mb_substitute_character();
		//if (!mb_substitute_character(0)) { // php only allows 0x0001...0xffff
		if (!mb_substitute_character(1)) {
			trigger_error("could not change substitute character", E_USER_WARNING);
		}
		$ticketnote = mb_convert_encoding($ticketnote, 'Windows-1252', 'UTF-8'); // store as Windows-1252 despite declaring latin1, see http://www.w3.org/TR/2009/WD-html5-20090423/infrastructure.html#character-encodings-0 or http://dev.w3.org/html5/spec/parsing.html#character-encodings-0
		mb_substitute_character($oldsu);
		//if (strpos($ticketnote, chr(0)) !== false) {
		if (strpos($ticketnote, chr(1)) !== false) {
			trigger_error("inv ticketnote", E_USER_WARNING);
			print "-2:0:could not convert character";
			exit;
		}
		$ticketnote = str_replace("\r\n", "\n", $ticketnote);
		$ticketnote = trim($ticketnote);
	}
	$ismoderator = $USER->hasPerm('moderator')?1:0; // FIXME ticketmod?
	$immediate = false;
	if (isset($_REQUEST['immediate'])) {
		$immediate = intval($_REQUEST['immediate']) != 0;
		if ($immediate && !$ismoderator) {
			trigger_error("immediate change without sufficient permissions", E_USER_WARNING);
			print "-1:0:only moderators can apply changes immediately";
			exit;
		}
	}
	$commitnotes = intval($_POST['commit']);
	// multiple commit:
	// * basic parameter check for all notes at the beginning
	// * $commitnotes contains the number of annotations to change/add
	// * uses $_REQUEST['id_'.$no], $no=2..$commitnotes additionally to $_REQUEST['id'], etc.
	// * concatenates all result strings, using '#' as separator:
	//   "-2:3:could not convert character#1:-2:123#..."  <-- could not change note with id 3, successfully added note -2 which has geonote_id 123, ...
	for ($i = 1; $i <= $commitnotes; ++$i) {
		$ret = check_params($i);
		if ($ret !== 0) {
			print $ret;
			exit;
		}
	}
	$gridimage_id = intval($_REQUEST["imageid"]);
	$image=new GridImage($gridimage_id);
	if (!$image->isValid()) {
		print "-5:0:invalid image id";
		exit;
	}
	$imagesize = $image->_getFullSize();
	$imagewidth = $imagesize[0];
	$imageheight = $imagesize[1];
	$isowner=($image->user_id==$USER->user_id)?1:0;

	$ret = commit_note(1, $gridimage_id, $imagewidth, $imageheight, $ismoderator, $isowner, $ticketnote, $immediate);
	for ($i = 2; $i <= $commitnotes; ++$i) {
		$ret .= '#' . commit_note($i, $gridimage_id, $imagewidth, $imageheight, $ismoderator, $isowner, $ticketnote, $immediate);
	}
	print $ret;
	$smarty = new GeographPage;
	$ab=floor($gridimage_id/10000);
	$smarty->clear_cache(null, "img$ab|$gridimage_id");
	exit;
}

$uid = 0;
$ticketid = 0;
$iniorigsize = 0;
if (isset($_GET['ticket'])) {
	$ticketid = intval($_GET['ticket']);
} elseif (isset($_GET['u'])) { /* ignored when there's a ticket parameter */
	$uid = intval($_GET['u']);
}
if (isset($_GET['size']) && $_GET['size'] == 'original') {
	$iniorigsize = 1;
}

if ($uid || $ticketid ) {
	$USER->mustHavePerm("basic");
}

$readonly = $USER->hasPerm("basic") ? 0 : 1;

$smarty = new GeographPage;

$template='geonotes.tpl';

$cacheid=0;

$image=new GridImage;

if (isset($_GET['id'])) {
	$id = intval($_GET['id']);
	$image->loadFromId($id);
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=$USER->hasPerm('moderator')?1:0; // FIXME ticketmod?

	//is the image accepted? - otherwise, only the owner and administrator should see it
	if (!$isowner&&!$ismoderator) {
		if ($image->moderation_status=='rejected') {
			//clear the image
			$image=new GridImage;
			$cacheid=0;
			$rejected = true;
		} elseif ($image->moderation_status=='pending') {
			//clear the image
			$image=new GridImage;
			$cacheid=0;
			$pending = true;
		}
	}
}

//do we have a valid image?
if ($image->isValid()) {
	if ($ticketid) {
		$ticket=new GridImageTroubleTicket($ticketid);
		if (!$ticket->isValid())
			die("invalid ticket id");
		if ($ticket->gridimage_id != $image->gridimage_id)
			die("ticket/image mismatch");
		$uid = $ticket->user_id;
	}
	if (!$uid) {
		$uid = $USER->user_id;
	}
	if ($uid != $USER->user_id && !$isowner && !$ticketid) { // FIXME tickets need to be treated differently (tickets by other useres if note not deleted (excluding pending or rejected changes)?)
		$USER->mustHavePerm("moderator"); // FIXME ticketmod?
	}
	#if (isset($_GET['note_id'])) {
	#   TODO
	#}

	$ab=floor($id/10000);

	// cache id must depend on user as we also display pending changes made by the user...
	$cacheid="img$ab|{$id}|notes|{$USER->user_id}_{$isowner}_{$ismoderator}_{$ticketid}_{$uid}_{$iniorigsize}_{$isloggedin}_{$readonly}"; # FIXME is caching still sensible?


	#//what style should we use?
	$style = 'white';
	#$style = $USER->getStyle();
	#$cacheid.=$style;

	//when this image was modified
	$mtime = strtotime($image->upd_timestamp);

	//page is unqiue per user (the profile and links)
	$hash = $cacheid.'.'.$USER->user_id;

	//can't use IF_MODIFIED_SINCE for logged in users as has no concept as uniqueness
	customCacheControl($mtime,$hash,($USER->user_id == 0));


	if (!$smarty->is_cached($template, $cacheid)) {
		$anystatus = array('visible', 'pending', 'deleted');
		$notdeleted = array('visible', 'pending');
		$selection = $isowner || $ismoderator ? $anystatus : $notdeleted;
		if ($ticketid) {
			$affectednotes = $ticket->getAffectedNotes();
			if ($affectednotes === false) {
				die("database error");
			}
			# test length of $affectednotes? currently only 1 possible...

			$notes =    $image->getNotes($selection, true, $uid /*FIXME or null?*/, $ticketid, false, null, $affectednotes, array('visible'));
			$oldnotes = $image->getNotes($anystatus, true, $uid,                    $ticketid, true,  $affectednotes);
			$newnotes = $image->getNotes($anystatus, true, $uid,                    $ticketid, false, $affectednotes);
			$smarty->assign_by_ref("oldnotes",$oldnotes);
			$smarty->assign_by_ref("newnotes",$newnotes);
			$smarty->assign_by_ref("ticket",$ticket);
		} else {
			$notes = $image->getNotes($selection, true, $uid);
		}
		$smarty->assign_by_ref("notes",$notes);

		$imagesize = $image->_getFullSize();

		$showorig = false;
		if ($image->original_width) {
			$smarty->assign('original_width', $image->original_width);
			$smarty->assign('original_height', $image->original_height);
			$smarty->assign('orig_url', $image->_getOriginalpath());
			// check if original size == std size // gbi could compare with 640, instead
			$uploadmanager=new UploadManager;
			list($destwidth, $destheight, $destdim, $changedim) = $uploadmanager->_new_size($image->original_width, $image->original_height);
			if ($changedim) {
				$showorig = true;
			}
		}
		$smarty->assign('std_width', $imagesize[0]);
		$smarty->assign('std_height', $imagesize[1]);
		$smarty->assign('img_url', $image->_getFullpath());
		$smarty->assign('showorig', $showorig);
		$smarty->assign('maincontentclass', 'content_photo'.$style);
		$smarty->assign('iniorigsize', $iniorigsize && $showorig);
		$smarty->assign('readonly', $readonly);

		//remove grid reference from title
		$image->bigtitle=trim(preg_replace("/^{$image->grid_reference}/", '', $image->title));
		$image->bigtitle=preg_replace('/(?<![\.])\.$/', '', $image->bigtitle);

		$rid = $image->grid_square->reference_index;
		$gridrefpref=$CONF['gridrefname'][$rid];
		$smarty->assign('page_title', $image->bigtitle.":: {$gridrefpref}{$image->grid_reference}");

		$smarty->assign('ismoderator', $ismoderator);
		$smarty->assign('isowner', $isowner);
		$smarty->assign_by_ref('image', $image);
	}
} elseif (!empty($rejected)) {
	header("HTTP/1.0 410 Gone");
	header("Status: 410 Gone");
} elseif (!empty($pending)) {
	header("HTTP/1.0 403 Forbidden");
	header("Status: 403 Forbidden");
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
}

function smarty_function_hidekeywords($input) {
	return preg_replace('/(^|[\n\r\s]+)(Keywords?[\s:][^\n\r>]+)$/','<span class="keywords">$2</span>',$input);
}
$smarty->register_modifier("hidekeywords", "smarty_function_hidekeywords");

$smarty->display($template, $cacheid);

?>
