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
$USER->login();


$template='editimage.tpl';
$cacheid='';


$image=new GridImage;

if (isset($_REQUEST['id']))
{
	$image->loadFromId($_REQUEST['id']);
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$isadmin=$USER->hasPerm('moderator')?1:0;
	
	if ($image->isValid())
	{
		
		//do our thing!
		$smarty->assign('page_title', $image->grid_reference);
		$smarty->assign_by_ref('image', $image);
		$smarty->assign_by_ref('isowner', $isowner);
		$smarty->assign_by_ref('isadmin', $isadmin);

		if ($_GET['thankyou'])
			$smarty->assign('thankyou', $_GET['thankyou']);


		//figure out what the user can and cannot edit
		$moderated=array();
		
		//assume everything is moderated
		$moderated["title"]=true;
		$moderated["comment"]=true;
		$moderated["imageclass"]=true;
		$moderated["imagetaken"]=true;
		$moderated["grid_reference"]=true;
		
		//now make some exceptions
		if ($isadmin)
		{
			$moderated["title"]=false;
			$moderated["comment"]=false;
			$moderated["imageclass"]=false;
			$moderated["imagetaken"]=false;
			$moderated["grid_reference"]=false;
		}
		elseif ($isowner)
		{
			$moderated["title"]=false;
			$moderated["comment"]=false;
			$moderated["imageclass"]=false;
			$moderated["imagetaken"]=false;
		
		 	if ($image->moderation_status == "pending")
				$moderated["grid_reference"]=false;
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

		
		$classes=&$image->getImageClasses();

		$imageclassother="";
		if (strlen($image->imageclass) && !in_array($image->imageclass, $classes))
		{
			$imageclassother=$image->imageclass;
			$image->imageclass="Other";
		}

		$smarty->assign_by_ref('classes', $classes);
		$smarty->assign_by_ref('imageclassother', $imageclassother);

		//process a trouble ticket?
		if (isset($_POST['gridimage_ticket_id']))
		{
			//ok, we're processing a ticket update, but lets 
			//exercise some healty paranoia..
			$gridimage_ticket_id=intval($_POST['gridimage_ticket_id']);
			$ticket=new GridImageTroubleTicket($gridimage_ticket_id);
			
			//you sure this is a ticket?
			if (!$ticket->isValid())
				die("invalid ticket id");
				
			//definitely for this image?
			if ($ticket->gridimage_id != $image->gridimage_id)
				die("ticket/image mismatch");
				
			//now lets do our thing depending on your permission level..
			$comment=stripslashes($_POST['comment']);
			if ($isadmin)
			{
				if (isset($_POST['addcomment']))
				{
					$ticket->addModeratorComment($USER->user_id, $comment);
				}
				elseif (isset($_POST['accept']))
				{
					$ticket->closeTicket($USER->user_id,$comment, isset($_POST['accepted'])?$_POST['accepted']:null);
					
					//reload the image
					$image->loadFromId($_REQUEST['id']);
	
				}
				elseif (isset($_POST['close']))
				{
					$ticket->closeTicket($USER->user_id,$comment);
				}
			}
			elseif ($isowner)
			{
				//add comment to ticket
				if (isset($_POST['addcomment']))
				{
					$ticket->addOwnerComment($USER->user_id, $comment);
					$smarty->assign("thankyou", "comment");
				}
			}
			else
			{
				die("naughty naughty. only moderators and image owners can update tickets.");
			}
			
			//refresh this page so you're less likely to repost
			header("Location: http://{$_SERVER['HTTP_HOST']}/editimage.php?id={$image->gridimage_id}");
				
		}
		
		

		//get trouble tickets
		$show_all_tickets = isset($_REQUEST['alltickets']) && $_REQUEST['alltickets']==1;
		$smarty->assign('show_all_tickets', $show_all_tickets);
		
		$statuses=array('pending', 'open');
		if ($show_all_tickets)
			$statuses[]='closed';
		
		$openTickets=&$image->getTroubleTickets($statuses);
		
		if (count($openTickets))
			$smarty->assign_by_ref('opentickets', $openTickets);

		
		//save changes?
		if (isset($_POST['title']))
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
			if ($moderated_count && (strlen($updatenote)==0))
			{
				$ok=false;
				$error['updatenote']="Please provide a brief comment about why the change is required";
			}
			
			$comment=trim(stripslashes($_POST['comment']));
			$comment=strip_tags($comment);
			
			/*
			if (strlen($comment)==0)
			{
				$ok=false;
				$error['comment']="Please provide a few comments about the image";
			}
			*/
			$imageclass=trim(stripslashes($_POST['imageclass']));
			$imageclass=strip_tags($imageclass);

			$imageclassother=trim(stripslashes($_POST['imageclassother']));
			$imageclassother=strip_tags($imageclassother);

			if (strlen($imageclass)==0)
			{
				$ok=false;
				$error['imageclass']="Please choose a geographical feature";
			}

			if ($imageclass=="Other")
			{
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

			//can't always specify this...
			if (isset($_POST['imagetakenYear']))
			{
				$imagetaken=sprintf("%04d-%d-%02d",$_POST['imagetakenYear'],$_POST['imagetakenMonth'],$_POST['imagetakenDay']);
			}
			else
			{
				$imagetaken=$image->imagetaken;
			}
			
			$sq=new GridSquare;
			$grid_reference=trim(stripslashes($_POST['grid_reference']));
			if (strlen($grid_reference))
			{
				if ($sq->setByFullGridRef($grid_reference))
				{
					//grid reference in $sq->grid_reference is OK, but might
					//be different to what we entered...
					if (strlen($sq->grid_reference) > strlen($grid_reference))
						$grid_reference=$sq->grid_reference;
						
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
			
			
			/////////////////////////////////////////////////////////////
			// STEP 2 - change control
			
			if ($ok)
			{
				//create new change control object
				$ticket=new GridImageTroubleTicket();
				$ticket->setSuggester($USER->user_id);
				if ($isadmin)
					$ticket->setModerator($USER->user_id);
					
				$ticket->setImage($_REQUEST['id']);
				$ticket->setNotes($updatenote);
				
				if (strlen($imageclassother))
					$imageclass=$imageclassother;

				//attach the various field changes
				$ticket->updateField("title", $image->title, $title, $moderated["title"]);
				$ticket->updateField("comment", $image->comment, $comment, $moderated["comment"]);
				$ticket->updateField("imageclass", $image->imageclass, $imageclass, $moderated["imageclass"]);
				$ticket->updateField("imagetaken", $image->imagetaken, $imagetaken, $moderated["imagetaken"]);
				$ticket->updateField("grid_reference", $image->grid_reference, $grid_reference, $moderated["grid_reference"]);
				
				//finalise the change ticket
				$status=$ticket->commit();
				
				
				//clear any caches involving this photo
				$smarty->clear_cache(null, "img{$image->gridimage_id}");

				//clear user specific stuff like profile page
				$smarty->clear_cache(null, "user{$image->user_id}");
				
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
			}
			else
			{
				//update the image with submitted data - smarty uses it to 
				//populate fields
				$image->title=$title;
				$image->comment=$comment;
				$image->imageclass=$imageclass;
				$image->imageclassother=$imageclassother;
				$image->imagetaken=$imagetaken;
				$image->grid_reference=$grid_reference;

				$smarty->assign_by_ref('updatenote', $updatenote);

				$smarty->assign_by_ref('error', $error);
			}
			
			
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
