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

//you must be logged in to request changes
$USER->mustHavePerm("basic");

if (isset($_POST['commit'])) { // change (id > 0) or add (id < 0) annotation
	// on change, return status + ':' + id
	// on successfull creation, return status + ':' + id + ':' + new note_id
	// on error return status + ':' + id + ':' + message or status + ':' + id
	// (use id=0 for errors not related to a specific note)
	// status:
	//    -6: invalid note id
	//    -5: invalid image id
	//    -4: missung parameters
	//    -3: invalid parameters
	//    -2: could not convert comment
	//    -1: internal error/access denied
	//    0:  applied changes
	//    1:  pending (awaiting moderation)
	//    2:  old values = new values, no changes made

	// FIXME invalidate cache of view.php and geonotes.php on success
	// FIXME also invalidate cache of geonotes.php when changing image details

	if (  !isset($_REQUEST['id'])
	    ||!isset($_REQUEST['imageid'])
	    ||!isset($_REQUEST['x1'])
	    ||!isset($_REQUEST['x2'])
	    ||!isset($_REQUEST['y1'])
	    ||!isset($_REQUEST['y2'])
	    ||!isset($_REQUEST['z'])
	    ||!isset($_REQUEST['status'])
	    ||!isset($_REQUEST['imgwidth'])
	    ||!isset($_REQUEST['imgheight'])
	    ||!isset($_REQUEST['comment'])) {
		print "-4:0:missing parameters";
		exit;
	}
	if (  !preg_match('/^[\x09\x0a\x0d\x20-\xff]*[\x21-\xff][\x09\x0a\x0d\x20-\xff]*$/', $_REQUEST['comment'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['id'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['imageid'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['x1'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['y1'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['x2'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['y2'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['z'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_POST['commit'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['imgwidth'])
	    ||!preg_match('/^\s*-?[0-9]+\s*$/', $_REQUEST['imgheight'])
	    ||$_REQUEST['status'] != 'visible' && $_REQUEST['status'] != 'deleted'
           ) {
		print "-3:0:invalid parameters";
		exit;
	}
	$commitnotes = intval($_POST['commit']);
	if ($commitnotes != 1) {
		// TODO allow multiple commit:
		//   check all parameters at the beginning (isset, preg_match, $note_id == 0, $commitnotes >= 1)
		//   $commitnotes contains the number of annotations to change/add
		//   use $_REQUEST['id_'+seqno], seqno=1..$commitnotes instead of $_REQUEST['id'], etc.
		//   concatenate all result strings, using '#' as separator: 
		//   "-2:3:could not convert character#1:-2:123#..."   <-- could not change note with id 3, successfully added note -2 which has id 123, now.
		print "-3:0:invalid parameters";
		exit;
	}
	$note_id = intval($_REQUEST['id']);
	if ($note_id == 0) {
		print "-3:0:invalid parameters";
		exit;
	}
	$comment = $_REQUEST['comment'];
	$oldsu = mb_substitute_character();
	//if (!mb_substitute_character(0)) { // php only allows 1...ffff ...
	if (!mb_substitute_character(1)) {
		trigger_error("could not change substitute character", E_USER_WARNING);
	}
	$comment = mb_convert_encoding($comment, 'Windows-1252', 'UTF-8'); // store as Windows-1252 despite declaring latin1, see http://en.wikipedia.org/wiki/Windows-1252
	mb_substitute_character($oldsu);
	//if (strpos($comment, chr(0)) !== false) {
	if (strpos($comment, chr(1)) !== false) {
		print "-2:$note_id:could not convert character";
		exit;
	}
	$comment = str_replace("\r\n", "\n", $comment);
	// FIXME trim comment?
	$status = $_REQUEST['status'];
	$x1 = intval($_REQUEST['x1']);
	$x2 = intval($_REQUEST['x2']);
	$y1 = intval($_REQUEST['y1']);
	$y2 = intval($_REQUEST['y2']);
	$iw = intval($_REQUEST['imgwidth']);
	$ih = intval($_REQUEST['imgheight']);
	$z  = intval($_REQUEST['z']);
	$gridimage_id = intval($_REQUEST['imageid']);
	$image=new GridImage;
	$image->loadFromId($gridimage_id);
	if (!$image->isValid()) {
		print "-5:$note_id:invalid image id";
		exit;
	}
	$imagesize = $image->_getFullSize();
	$imagewidth = $imagesize[0];
	$imageheight = $imagesize[1];
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=$USER->hasPerm('moderator')?1:0;
	if (   $x1 < 0 || $x2 >= $iw
	    || $y1 < 0 || $y2 >= $ih
	    || $iw < $imagewidth || $ih < $imageheight
	    || $z < -10 || $z > 10
	    || ($x2 - $x1 + 1)*$imagewidth  < 15*$iw // minimal width/height in std size ("640"): 15 pixels
	    || ($y2 - $y1 + 1)*$imageheight < 15*$ih
	    || ($note_id < 0) && $status == 'deleted'
	   ) {
		print "-3:$note_id:invalid parameters";
		exit;
	}

	$ticket=new GridImageTroubleTicket();
	$ticket->setSuggester($USER->user_id,$USER->realname);
	if ($ismoderator && !$isowner)
		$ticket->setModerator($USER->user_id);
	//$ticket->setType('normal'); // FIXME?
	$ticket->setPublic('everyone'); // FIXME?
	$ticket->setImage($gridimage_id);
	$mod = !($isowner||$ismoderator); # FIXME change to something like $mod=!($isowner||$ismoderator&&$immediate) with $immediate corresponding to some new control...
	// FIXME optional comment by the user

	$note=new GridImageNote;
	if ($note_id > 0) {
		# change existing annotation using the $ticket
		$note->loadFromId($note_id);
		if (!$note->isValid() || $note->gridimage_id != $gridimage_id) {
			print "-6:$note_id:invalid note id";
			exit;
		}
		$ticket->setNotes("Changed image annotation $note_id.");
		$ticket->updateField('x1', $note->x1, $x1, $mod, $note_id);
		$ticket->updateField('x2', $note->x2, $x2, $mod, $note_id);
		$ticket->updateField('y1', $note->y1, $y1, $mod, $note_id);
		$ticket->updateField('y2', $note->y2, $y2, $mod, $note_id);
		$ticket->updateField('z', $note->z, $z, $mod, $note_id);
		$ticket->updateField('imgwidth', $note->imgwidth, $iw, $mod, $note_id);
		$ticket->updateField('imgheight', $note->imgheight, $ih, $mod, $note_id);
		$ticket->updateField('comment', $note->comment, $comment, $mod, $note_id);
		$ticket->updateField('status', $note->status, $status, $mod, $note_id);
		// FIXME updateField should report errors and we should pass that to the client
		$reqinfo = '';
	} else {
		# add new annotation with status 'pending' and change the status to 'visible' using the $ticket
		# (this way, the user gets a notification mail and we get an "annotation history")
		$newnote_id =  $note->create($gridimage_id, $x1, $x2, $y1, $y2, $iw, $ih, $comment, $z, 'pending');
		if (!$note->isValid()) {
			print "-1:$note_id:could not create annotation";
			exit;
		}
		$ticket->setNotes("Created image annotation $newnote_id.");
		$ticket->updateField('status', 'pending', 'visible', $mod, $newnote_id);
		$reqinfo = ":$newnote_id";
	}
	if (!$ticket->commit_count) {
		print("2:$note_id");
		exit;
	}
	if ($ticket->commit() == 'closed') { // FIXME error handling...
		print "0:$note_id$reqinfo";
	} else {
		print "1:$note_id$reqinfo";
	}
	#$ab=floor($image->gridimage_id/10000);
	#$smarty->clear_cache(null, "img$ab|{$image->gridimage_id}");
	#FIXME change our cache_id accordingly?
	exit;
}

$smarty = new GeographPage;

$template='geonotes.tpl';

$cacheid=0;

#if ($smarty->caching) {
#	$smarty->caching = 2; // lifetime is per cache
#	$smarty->cache_lifetime = 3600*3; //3hour cache
#}

$image=new GridImage;

if (isset($_GET['id']))
{
	$id = intval($_GET['id']);
	$image->loadFromId($id);
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=$USER->hasPerm('moderator')?1:0;

	$ab=floor($id/10000);

	//$cacheid="geonote$ab|{$id}|{$isowner}_{$ismoderator}";
	$cacheid="img$ab|{$id}|notes|{$USER->user_id}_{$isowner}_{$ismoderator}"; # FIXME is caching still sensible?
	// cache id must depend on user as we also display pending changes made by the user...

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
if ($image->isValid())
{
	if (!$isowner&&!$ismoderator) {
		$notes = $image->getNotes(array('visible')); # FIXME       add pending changes made by this user, add deleted notes (but only display in form)
	} else {
		$notes = $image->getNotes(array('visible', 'pending', 'deleted')); # FIXME       add pending changes made by this user, add deleted notes (but only display in form)
	}

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


	if (!$smarty->is_cached($template, $cacheid))
	{
		$imagesize = $image->_getFullSize();

		#$sizes = array();
		#$widths = array();
		#$heights = array();
		#$showorig = false;
		#if ($image->original_width) {
		#	$smarty->assign('original_width', $image->original_width);
		#	$smarty->assign('original_height', $image->original_height);
		#	$uploadmanager=new UploadManager;
		#	list($destwidth, $destheight, $maxdim, $changedim) = $uploadmanager->_new_size($image->original_width, $image->original_height);
		#	if ($changedim) {
		#		$showorig = true;
		#		foreach ($CONF['show_sizes'] as $cursize) {
		#			list($destwidth, $destheight, $destdim, $changedim) = $uploadmanager->_new_size($image->original_width, $image->original_height, $cursize);
		#			if (!$changedim)
		#				break;
		#			$sizes[] = $cursize;
		#			$widths[] = $destwidth;
		#			$heights[] = $destheight;
		#			$maxdim = $destdim;
		#		}
		#		$maxdim = max($image->original_width, $image->original_height);
		#	}
		#} else {
		#	$maxdim = max($imagesize[0], $imagesize[1]);
		#}

		$showorig = false;

		if ($image->original_width) {
			$smarty->assign('original_width', $image->original_width);
			$smarty->assign('original_height', $image->original_height);
			$uploadmanager=new UploadManager;
			list($destwidth, $destheight, $maxdim, $changedim) = $uploadmanager->_new_size($image->original_width, $image->original_height);
			if ($changedim) {
				$showorig = true;
				$maxdim = $destdim;
				$maxdim = max($image->original_width, $image->original_height); # FIXME?
			}
		} else {
			$maxdim = max($imagesize[0], $imagesize[1]);
		}
		#$smarty->assign('sizes', $sizes);
		#$smarty->assign('widths', $widths);
		#$smarty->assign('heights', $heights);
		$smarty->assign('stdsize', $CONF['img_max_size']);
		$smarty->assign('originalsize', $maxdim);
		$smarty->assign('std_width', $imagesize[0]);
		$smarty->assign('std_height', $imagesize[1]);

		$smarty->assign('showorig', $showorig);
		$smarty->assign('orig_url', $image->_getOriginalpath());
		$smarty->assign('img_url', $image->_getFullpath());
		$smarty->assign('maincontentclass', 'content_photo'.$style);
		$smarty->assign_by_ref("notes",$notes);

		//remove grid reference from title
		$image->bigtitle=trim(preg_replace("/^{$image->grid_reference}/", '', $image->title));
		$image->bigtitle=preg_replace('/(?<![\.])\.$/', '', $image->bigtitle);

		$rid = $image->grid_square->reference_index;
		$gridrefpref=$CONF['gridrefname'][$rid];
		$smarty->assign('page_title', $image->bigtitle.":: {$gridrefpref}{$image->grid_reference}");

		$smarty->assign('ismoderator', $ismoderator);
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
