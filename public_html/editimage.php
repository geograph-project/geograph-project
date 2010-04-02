<?php
/**
 * $Project: GeoGraph $
 * $Id$
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


/*
to do

grab all pending/open tickets for image (should be method of gridimage)
add to smarty for display

moderator can approve changes

Open Change Requests
Submitted by, Date, last modified
note
field from to (editable) tools (accept/reject radios)
status+save button for each (email sent to owner,submitter)
quick message button formats all the info into an email directing the user
to comment

non-owner gets basic info

owner gets basic info, opportunity to add a new note (email sent to mods)
*/


require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimagetroubleticket.class.php');

init_session();

$smarty = new GeographPage;

//you must be logged in to request changes
$USER->mustHavePerm("basic");


$template='editimage.tpl';
$cacheid='';


$image=new GridImage;

	//should we display a thumb
	$thumb=false;
	if (isset($_GET['thumb']))
	{
		$thumb=(bool)$_GET['thumb'];
		$_SESSION['thumb']=$thumb;
		//ToDo - if logged in user, save this in profile
	}
	elseif (false) //if logged in user
	{
			//get setting from profile
	}
	elseif (isset($_SESSION['thumb']))
	{
		$thumb=$_SESSION['thumb'];

	}
	$smarty->assign('thumb', $thumb);	

if (isset($_REQUEST['id']))
{
	$image->loadFromId($_REQUEST['id']);
	
	if (!empty($CONF['use_insertionqueue']) && isset($image->unavailable)) {
		
		header("HTTP/1.1 503 Service Unavailable");
		$smarty->display("image_notready.tpl");

		exit;
	}
	
	
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$isadmin=(!$isowner && $USER->hasPerm('ticketmod'))?1:0;

	if ($image->isValid())
	{
		if ($image->moderation_status=='rejected')
		{
			if ($isowner||$isadmin)
			{
				//ok, we'll let it lie...
			}
			else
			{
				header("Location: /photo/{$_REQUEST['id']}");
				exit;
			}
		}
		if (isset($_GET['unlock'])) {
			$image->unlockThisImage($USER->user_id);
			die(1);
		}

		//get the grid references
		$image->getSubjectGridref();
		$image->getPhotographerGridref();
		
		//save these so can be used as title etc on the main image (when following link from search results, and redoing changes etc)
		$vars=get_object_vars($image);
		foreach($vars as $name=>$val)
		{
			if ($name!="db")
				$image->{"current_$name"} = $image->$name;
		}

		//do our thing!
		$smarty->assign('page_title', $image->grid_reference);
		$smarty->assign_by_ref('image', $image);
		$smarty->assign_by_ref('isowner', $isowner);
		$smarty->assign_by_ref('isadmin', $isadmin);

		if (!empty($_GET['thankyou']))
			$smarty->assign('thankyou', $_GET['thankyou']);


		//figure out what the user can and cannot edit
		$moderated=array();

		//assume everything is moderated
		$moderated["title"]=true;
		$moderated["comment"]=true;
		$moderated["imageclass"]=true;
		$moderated["imagetaken"]=true;
		$moderated["grid_reference"]=true;
		$moderated["photographer_gridref"]=true;
		$moderated["view_direction"]=true;
		$moderated["use6fig"]=true;


		//now make some exceptions
		if ($isadmin && (!empty($_REQUEST['mod']) && $_REQUEST['mod'] == 'apply') )
		{
			$moderated["title"]=false;
			$moderated["comment"]=false;
			$moderated["imageclass"]=false;
			$moderated["imagetaken"]=false;
			$moderated["grid_reference"]=false;
			$moderated["photographer_gridref"]=false;
			$moderated["view_direction"]=false;
			$moderated["use6fig"]=false;
		}
		elseif ($isowner)
		{
			$moderated["title"]=false;
			$moderated["comment"]=false;
			$moderated["imageclass"]=false;
			$moderated["imagetaken"]=false;

			if ($image->moderation_status == "pending")
				$moderated["grid_reference"]=false;

			$moderated["photographer_gridref"]=false;
			$moderated["view_direction"]=false;
			$moderated["use6fig"]=false;
		}


		$smarty->assign_by_ref('moderated', $moderated);

		//how many moderated fields?
		$moderated_count=0;
		foreach($moderated as $field=>$status)
		{
			if ($status)
				$moderated_count++;
		}
		$smarty->assign('moderated_count', $moderated_count);
		$smarty->assign('all_moderated', $moderated_count==count($moderated));

		//when starting we dont use imageclassother
		$smarty->assign('imageclassother', '');




		//process a trouble ticket?
		if (isset($_POST['gridimage_ticket_id']))
		{
			//we really need this not be interupted
			ignore_user_abort(TRUE);
			set_time_limit(3600);

			
			//ok, we're processing a ticket update, but lets
			//exercise some healty paranoia..
			$gridimage_ticket_id=intval($_POST['gridimage_ticket_id']);
			$ticket=new GridImageTroubleTicket($gridimage_ticket_id);

			//you sure this is a ticket?
			if (!$ticket->isValid())
				die("invalid suggestion id");

			//definitely for this image?
			if ($ticket->gridimage_id != $image->gridimage_id)
				die("suggestion/image mismatch");
			
			$issuggester=($ticket->user_id==$USER->user_id)?1:0;
	
			
			if (!$issuggester) {
				$ticket->setNotify((!empty($_POST['notify']))?preg_replace('/[^\w]+/','',$_POST['notify']):'');
			}
			
			$thankyou = '';
			
			//now lets do our thing depending on your permission level..
			$comment=stripslashes($_POST['comment']);
			if ($isadmin)
			{
				if (isset($_POST['disown']))
				{
					$ticket->removeModerator();
				} 
				elseif (isset($_POST['addcomment']))
				{
					$ticket->addModeratorComment($USER->user_id, $comment, !empty($_POST['claim']));
				}
				elseif (isset($_POST['accept']))
				{
					$ticket->setModerator($USER->user_id); 
					$ticket->closeTicket($USER->user_id,$comment, isset($_POST['accepted'])?$_POST['accepted']:null);

					//reload the image
					$image->loadFromId($_REQUEST['id']);

				}
				elseif (isset($_POST['close']))
				{
					$ticket->setModerator($USER->user_id); 
					$ticket->closeTicket($USER->user_id,$comment);
				}
			}
			elseif ($isowner)
			{
				//add comment to ticket
				if (isset($_POST['addcomment']))
				{
					$ticket->addOwnerComment($USER->user_id, $comment);
					#$smarty->assign("thankyou", "comment");
					$thankyou="comment";
				}
			}
			elseif ($issuggester)
			{
				//add comment to ticket
				if (isset($_POST['addcomment']))
				{
					$ticket->addSuggesterComment($USER->user_id, $comment);
					#$smarty->assign("thankyou", "comment");
					$thankyou="comment";
				}
			}
			else
			{
				die("naughty naughty. only moderators and image owners can update tickets.");
			}
			
			if (isset($_SESSION['editpage_options']) && in_array('small_redirect',$_SESSION['editpage_options'])) {
				header("Location: http://{$_SERVER['HTTP_HOST']}/thankyou.php#thankyou=done&id={$_REQUEST['id']}");
				exit;
			}
			
			//refresh this page so you're less likely to repost
			header("Location: http://{$_SERVER['HTTP_HOST']}/editimage.php?id={$image->gridimage_id}".($thankyou?"&thankyou=$thankyou":''));
			exit;
		}

		if ($moderator = $image->isImageLocked($USER->user_id)) {
			$smarty->assign("locked_by_moderator", $moderator);
		} else {
			$image->lockThisImage($USER->user_id);
		}

		//save changes?
		if (isset($_POST['title']) && isset($_POST['apply']))
		{
			$ok=true;
			$error=array();

			/////////////////////////////////////////////////////////////
			// STEP 1 - first we simply validate what'd been passed

			//get and parse the form fields
			$title=trim(stripslashes($_POST['title']));
			$title=strip_tags($title);
			if (strlen($title)==0)
			{
				$ok=false;
				$error['title']="Please specify an image title";
			}
			
			$comment=trim(stripslashes($_POST['comment']));
			$comment=strip_tags($comment);
			
			/////////////////////////////////////////////////////////////
			// STEP 2 - change control

			if ($isowner && $ok)
			{
				//we really need this not be interupted
				ignore_user_abort(TRUE);
				set_time_limit(3600);

				//create new change control object
				$ticket=new GridImageTroubleTicket();
				$ticket->setSuggester($USER->user_id,$USER->realname);
			
				$ticket->setImage($_REQUEST['id']);
				$ticket->setPublic('everyone');
				
				
				$ticket->setNotes("Automatic - recording changes applied directly");

				//attach the various field changes
				$ticket->updateField("title", $image->title, $title, $moderated["title"]);
				$ticket->updateField("comment", $image->comment, $comment, $moderated["comment"]);
				
				if (!empty($ticket->changes) && count($ticket->changes)) {
					$status=$ticket->commit();

					//clear any caches involving this photo
					$ab=floor($image->gridimage_id/10000);
					$smarty->clear_cache(null, "img$ab|{$image->gridimage_id}");

					//clear user specific stuff like profile page
					$ab=floor($image->user_id/10000);
					$smarty->clear_cache(null, "user$ab|{$image->user_id}");

					header("Location: http://{$_SERVER['HTTP_HOST']}/thankyou.php#thankyou=$status&id={$_REQUEST['id']}");
				} else {
					header("Location: http://{$_SERVER['HTTP_HOST']}/thankyou.php#nochanges&id={$_REQUEST['id']}");
				}
				exit;
			}
			
			/////////////////////////////////////////////////////////////
			// fall back...
			
			#NOTE: we used 'create' as the fallback - it handles submission of title and comment ONLY - if add other fields to apply, will need to make this cope. 
			$_POST['create'] = true;
		} 
		elseif (isset($_POST['title']) && !isset($_POST['create']))
		{
			$ok=true;
			$error=array();

			/////////////////////////////////////////////////////////////
			// STEP 1 - first we simply validate what'd been passed

			//get and parse the form fields
			$title=trim(stripslashes($_POST['title']));
			$title=strip_tags($title);
			if (strlen($title)==0)
			{
				$ok=false;
				$error['title']="Please specify an image title";
			}

			$updatenote=trim(stripslashes($_POST['updatenote']));
			$updatenote=strip_tags($updatenote);
			if (($moderated_count || (!empty($_REQUEST['mod']) && $_REQUEST['mod'] == 'pending')) && (strlen($updatenote)==0))
			{
				$ok=false;
				if (!empty($_REQUEST['mod']) && $_REQUEST['mod'] == 'pending')
					$smarty->assign('mod_pending',1);
				$error['updatenote']="Please provide a brief comment about why the change is required";
			}

			$comment=trim(stripslashes($_POST['comment']));
			$comment=strip_tags($comment);

			$imageclass=trim(stripslashes($_POST['imageclass']));
			$imageclass=strip_tags($imageclass);

			$imageclassother=trim(stripslashes($_POST['imageclassother']));
			$imageclassother=strip_tags($imageclassother);

			$tmp_class = $imageclass;
			if (strlen($imageclass)==0)
			{
				$ok=false;
				$error['imageclass']="Please choose a geographical feature";
			}

			if ($imageclass=="Other")
			{
				$tmp_class = $imageclassother;
				if (strlen($imageclassother)==0)
				{
					$ok=false;
					$error['imageclassother']="Please specify the geographical feature";
				}
			}
			else
			{
				$imageclassother="";
			}
			if (preg_match('/^(Supplemental|Geograph\b|Accept)/i',$tmp_class) && $tmp_class != 'Geograph meet') {
				$ok=false;
				$error['imageclass']="Please choose a geographical feature";
			}
						
			//can't always specify this...
			if (isset($_POST['imagetakenYear']))
			{
				$imagetaken=sprintf("%04d-%02d-%02d",$_POST['imagetakenYear'],$_POST['imagetakenMonth'],$_POST['imagetakenDay']);
			}
			else
			{
				$imagetaken=$image->imagetaken;
			}

			$sq=new GridSquare;
			$grid_reference=trim(stripslashes($_POST['grid_reference']));
			if (strlen($grid_reference))
			{
				if ($sq->setByFullGridRef($grid_reference,false,true))
				{
					//grid reference in $sq->grid_reference is OK, but might
					//be different to what we entered...
					if (strlen($sq->grid_reference) > strlen($grid_reference))
						$grid_reference=$sq->grid_reference;

					if ($isowner && $sq->gridsquare_id == $image->gridsquare_id) {
						$moderated["grid_reference"]=false;
					}
				}
				else
				{
					$ok=false;
					$error['grid_reference']=$sq->errormsg;
				}
			}
			else
			{
				$ok=false;
				$error['grid_reference']="Please specify a grid reference";
			}


			$sq=new GridSquare;
			$photographer_gridref=trim(stripslashes($_POST['photographer_gridref']));
			if (strlen($photographer_gridref))
			{
				if ($sq->setByFullGridRef($photographer_gridref,false,true))
				{
					//grid reference in $sq->grid_reference is OK, but might
					//be different to what we entered...
					if (strlen($sq->grid_reference) > strlen($photographer_gridref))
						$photographer_gridref=$sq->grid_reference;

				}
				else
				{
					$ok=false;
					$error['photographer_gridref']=$sq->errormsg;
				}
			}

			$view_direction=intval(trim(stripslashes($_POST['view_direction'])));
			$use6fig=intval(trim(stripslashes($_POST['use6fig'])));


			/////////////////////////////////////////////////////////////
			// STEP 2 - change control

			if ($ok)
			{
				//we really need this not be interupted
				ignore_user_abort(TRUE);
				set_time_limit(3600);

				//create new change control object
				$ticket=new GridImageTroubleTicket();
				$ticket->setSuggester($USER->user_id,$USER->realname);
				if ($isadmin && !empty($_REQUEST['mod']))
					$ticket->setModerator($USER->user_id);

				if (!empty($_REQUEST['type'])) 
					$ticket->setType($_REQUEST['type']);
				$ticket->setPublic(isset($_REQUEST['public'])?$_REQUEST['public']:'everyone');
				
				$ticket->setImage($_REQUEST['id']);
				$ticket->setNotes($updatenote);

				if (strlen($imageclassother))
					$imageclass=$imageclassother;

				//attach the various field changes
				$ticket->updateField("title", $image->title, $title, $moderated["title"]);
				$ticket->updateField("comment", $image->comment, $comment, $moderated["comment"]);
				$ticket->updateField("imageclass", $image->imageclass, $imageclass, $moderated["imageclass"]);
				$ticket->updateField("imagetaken", $image->imagetaken, $imagetaken, $moderated["imagetaken"]);
				$ticket->updateField("grid_reference", $image->subject_gridref, $grid_reference, $moderated["grid_reference"]);
				$ticket->updateField("photographer_gridref", $image->photographer_gridref, $photographer_gridref, $moderated["photographer_gridref"]);
				$ticket->updateField("view_direction", $image->view_direction, $view_direction, $moderated["view_direction"]);
				$ticket->updateField("use6fig", $image->use6fig, $use6fig, $moderated["use6fig"]);

				//finalise the change ticket
				if (!empty($_REQUEST['mod'])) {
					switch ($_REQUEST['mod']) {
						//owner has choosen to notify a modeator
						case 'pending': $status=$ticket->commit('pending'); break;
						
						//a modwerator wants to close the ticket
						case 'apply': $status=$ticket->commit('closed'); break;
						
						//a modwerator wants to own the ticket
						case 'assign': $status=$ticket->commit('open'); break;
						
						default: $status=$ticket->commit(); break;
					}
				} else {
					$status=$ticket->commit();
				}
				
				//clear any caches involving this photo
				$ab=floor($image->gridimage_id/10000);
				$smarty->clear_cache(null, "img$ab|{$image->gridimage_id}");

				//clear user specific stuff like profile page
				$ab=floor($image->user_id/10000);
				$smarty->clear_cache(null, "user$ab|{$image->user_id}");
				
				if (isset($_SESSION['editpage_options']) && in_array('small_redirect',$_SESSION['editpage_options'])) {
					header("Location: http://{$_SERVER['HTTP_HOST']}/thankyou.php#thankyou=$status&id={$_REQUEST['id']}");
					exit;
				}
				
				//return to this edit screen with a thankyou
				if ($status=="pending")
				{
					//since we can't process the changes, show the user the edit page with a thankyou
					header("Location: http://{$_SERVER['HTTP_HOST']}/editimage.php?id={$image->gridimage_id}&thankyou=$status");
				}
				else
				{
					//all edits are complete, so lets show the user the result of their handiwork
					header("Location: http://{$_SERVER['HTTP_HOST']}/photo/{$image->gridimage_id}");
				}
				exit;
			}
			else
			{
				//update the image with submitted data - smarty uses it to
				//populate fields
				$image->title=$title;
				$image->comment=$comment;
				$image->imageclass=$imageclass;
				$image->imagetaken=$imagetaken;
				$image->subject_gridref=$grid_reference;
				$image->photographer_gridref=$photographer_gridref;
				$image->view_direction=$view_direction;
				$image->use6fig=$use6fig;

				$smarty->assign_by_ref('updatenote', $updatenote);

				$smarty->assign_by_ref('error', $error);
				
				$smarty->assign('imageclassother',$imageclassother);
				
			}


		}
		if (isset($_GET['simple'])) {
			if (empty($_GET['simple'])) {
				if (isset($_SESSION['editpage_options']) && ($i = array_search('simple',$_SESSION['editpage_options']))!==FALSE) {
					unset($_SESSION['editpage_options'][$i]);
				}
			} else {
				if (!isset($_SESSION['editpage_options'])) {
					$_SESSION['editpage_options'] = array();
				}
				$_SESSION['editpage_options'][] = 'simple';
			}
		} 
		if (isset($_GET['form'])) {
			$smarty->assign('showfull', 0);
			
		} elseif (!isset($_SESSION['editpage_options']) || !in_array('simple',$_SESSION['editpage_options'])) {
			
			$smarty->assign('showfull', 1);
			
			if ($CONF['forums']) {
				//let's find posts in the gridref discussion forum
				$image->grid_square->assignDiscussionToSmarty($smarty);
			}
		}
		
		$smarty->assign('use_autocomplete', $USER->use_autocomplete);
		
		require_once('geograph/rastermap.class.php');

		$rastermap = new RasterMap($image->grid_square,true);
		if (!empty($image->viewpoint_northings)) {
			$rastermap->addViewpoint($image->viewpoint_eastings,$image->viewpoint_northings,$image->viewpoint_grlen,$image->view_direction);
		} elseif (isset($image->view_direction) && strlen($image->view_direction) && $image->view_direction != -1) {
			$rastermap->addViewDirection($image->view_direction);
		}
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		list($lat,$long) = $conv->gridsquare_to_wgs84($image->grid_square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);
		$rastermap->addLatLong($lat,$long);

		$smarty->assign_by_ref('rastermap', $rastermap);

		//build a list of view directions
		$dirs = array (-1 => '');
		$jump = 360/16; $jump2 = 360/32;
		for($q = 0; $q< 360; $q+=$jump) {
			$s = ($q%90==0)?strtoupper(heading_string($q)):ucwords(heading_string($q));
			$dirs[$q] = sprintf('%s : %03d deg (%03d > %03d)',
				str_pad($s,16,' '),
				$q,
				($q == 0?$q+360-$jump2:$q-$jump2),
				$q+$jump2);
		}
		$dirs['00'] = $dirs[0];
		$smarty->assign_by_ref('dirs', $dirs);

		if (!isset($_SESSION['editpage_options']) || !in_array('simple',$_SESSION['editpage_options'])) {

			//get trouble tickets
			$show_all_tickets = isset($_REQUEST['alltickets'])?intval($_REQUEST['alltickets']):1;
			$smarty->assign('show_all_tickets', $show_all_tickets);

			$statuses=array('pending', 'open');
			if ($show_all_tickets)
				$statuses[]='closed';

			$openTickets=&$image->getTroubleTickets($statuses);

			if (count($openTickets))
				$smarty->assign_by_ref('opentickets', $openTickets);

			$image->lookupModerator();
			$image->loadSnippets();
		}
		
		
		if (isset($_POST['title']) && isset($_POST['create']))
		{
			$title=trim(stripslashes($_POST['title']));
			$title=strip_tags($title);

			$comment=trim(stripslashes($_POST['comment']));
			$comment=strip_tags($comment);
			
			$image->title=$title;
			$image->comment=$comment;
		}
		//strip out zeros from date
		#$image->imagetaken=str_replace('0000-', '-', $image->imagetaken);
		#$image->imagetaken=str_replace('-00', '-', $image->imagetaken);




	}
	else
	{
		$smarty->assign('error', 'Invalid image id specified');
	}

}
else
{
	$smarty->assign('error', 'No image id specified');
}

$smarty->display($template, $cacheid);


?>
