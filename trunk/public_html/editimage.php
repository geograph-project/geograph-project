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

require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;

$template='editimage.tpl';
$cacheid='';


$image=new GridImage;

if (isset($_REQUEST['id']))
{
	$image->loadFromId($_REQUEST['id']);
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$isadmin=$USER->hasPerm('admin')?1:0;
	
	if ($image->isValid())
	{
		if ($isadmin || $isowner)
		{
			//do our thing!
			$smarty->assign('page_title', $image->grid_reference);
			$smarty->assign_by_ref('image', $image);
			
			
			
			$classes=&$image->getImageClasses();
			
			$imageclassother="";
			if (strlen($image->imageclass) && !in_array($image->imageclass, $classes))
			{
				$imageclassother=$image->imageclass;
				$image->imageclass="Other";
			}
			
			$smarty->assign_by_ref('classes', $classes);
			$smarty->assign_by_ref('imageclassother', $imageclassother);


			//save changes?
			if (isset($_POST['title']))
			{
				$ok=true;
				$error=array();
				
				$title=trim(stripslashes($_POST['title']));
				$title=strip_tags($title);
				if (strlen($title)==0)
				{
					$ok=false;
					$error['title']="Please specify an image title";
				}
				
				$comment=trim(stripslashes($_POST['comment']));
				$comment=strip_tags($comment);
				if (strlen($comment)==0)
				{
					$ok=false;
					$error['comment']="Please provide a few comments about the image";
				}
				
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
				
				$imagetaken=sprintf("%04d-%d-%02d",$_POST['imagetakenYear'],$_POST['imagetakenMonth'],$_POST['imagetakenDay']);
				
				$image->title=$title;
				$image->comment=$comment;
				$image->imageclass=$imageclass;
				$image->imageclassother=$imageclassother;
				$image->imagetaken=$imagetaken;
				
				
				//change grid reference?
				if ($ok &&
				    (($image->moderation_status=='pending')||($isadmin)) &&
					($_POST['grid_reference']!=$image->grid_reference))
				{
					//we are allowed to change the reference, and it has definitely changed...
					
					$ok=$image->reassignGridsquare($_POST['grid_reference'], $err);
					if (!$ok)
						$error['grid_reference']=$err;
				}
				
				if ($ok)
				{
				 	//save changes
				 	if (strlen($imageclassother))
				 		$image->imageclass=$imageclassother;
				 	
				 	//clear caches involving the image
					$smarty->clear_cache(null, "img{$image->gridimage_id}");
					
					//clear user specific stuff like profile page
					$smarty->clear_cache(null, "user{$image->user_id}");
		
				 	$image->commitChanges();
				 	header("Location: http://{$_SERVER['HTTP_HOST']}/view.php?id={$image->gridimage_id}");
				 	exit;
				}
				else
				{
					$smarty->assign_by_ref('error', $error);
				}
			}	
			
			//strip out zeros from date
			#$image->imagetaken=str_replace('0000-', '-', $image->imagetaken);
			#$image->imagetaken=str_replace('-00', '-', $image->imagetaken);
			

		}
		else
		{
			$smarty->assign('error', 'You cannot edit another user\'s images');
		}
		
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
