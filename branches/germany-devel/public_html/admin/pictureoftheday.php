<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 3147 2007-03-08 00:18:25Z barry $
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
  #a null date indicates a candidate image which could be automatically
  #used to fill a void
	create table gridimage_daily
	(
	gridimage_id int not null,
	showday date,
	primary key(gridimage_id),
	index(showday)
	);
  
 */
	 
require_once('geograph/global.inc.php');
require_once('geograph/gridimage.class.php');
init_session();

$USER->hasPerm("admin") || $USER->mustHavePerm("moderator");

$smarty = new GeographPage;

$template='admin_pictureoftheday.tpl';
$cacheid=$USER->user_id;
$smarty->caching=0;

if (!$smarty->is_cached($template, $cacheid))
{
	$daysperimg=$CONF['potd_daysperimage'];
	$upper=$daysperimg-1;
	$lower=-$daysperimg+1;
	//lets get some stats
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	
	//handle form post
	if (isset($_POST['addimage']))
	{
		$smarty->assign("addimage",$_POST['addimage']);
		$smarty->assign("when",$_POST['when']);
		
		$gridimage_id=intval($_POST['addimage']);
		if ($gridimage_id)
		{
			$error="";
			
			$image=new GridImage($gridimage_id);
			if ($image->moderation_status=="geograph" || 
				$image->moderation_status=="accepted")
			{
				$when=trim($_POST['when']);
				if (strlen($when))
				{
					$t=strtotime($when);
					$today=strtotime("today");
					
					$d=strftime("%a, %d-%b-%Y %H:%M", $t);
					
					if ($t<$today)
					{
						$error="$when evaluated as a past day ($d)";
					}
					else
					{
						$showday=strftime("'%Y-%m-%d'", $t);
					
						$assigned_id=$db->GetOne("select gridimage_id from gridimage_daily where showday=$showday");
						if ($assigned_id)
							$error="There is already an image $assigned_id assigned for $d";	
				
					}
					
				}
				else
				{
					$showday="NULL";
				}
				
				//have we used this image already?
				if(strlen($error)==0)
				{
					$assigned=$db->GetRow("select showday from gridimage_daily where gridimage_id=$gridimage_id");
					
					
					if (is_array($assigned) && count($assigned))
					{
						if (is_null($assigned['showday']))
						{
							$assigned_t=time()+86400; //the future!
							$assigned_when="the next available empty slot";
						}
						else
						{
							$assigned_t=strtotime($assigned['showday']);
							$assigned_when=$assigned['showday'];
							
							
						}
						
						$today=strtotime("today");
						if ($assigned_t<$today)
						{
							$error="Image $assigned_id has already been featured on $assigned_when";
						}
						else
						{
							$error="Image $assigned_id has already been assigned to $assigned_when";
						}	
					}
					
				}
				
				if(strlen($error)==0)
				{
					//woo yay - go for it
					$db->Execute("insert into gridimage_daily(gridimage_id,showday)values($gridimage_id,$showday)");
					if ($showday=="NULL")
						$smarty->assign("confirm","Image $gridimage_id will be shown on a day when no image has been assigned");
					else
						$smarty->assign("confirm","Image $gridimage_id will be shown on $d");
				}
			}
			else
			{
				if ($image->gridimage_id)
					$error="Image $gridimage_id is {$image->moderation_status} so can't be used!'";
				else
					$error="Invalid image id";
					
			}
			
			if (strlen($error))
				$smarty->assign("error",$error);
		}
		
		
		
	}
	
	
	//count how many are in the kitty
	$pending=$db->GetCol("select gridimage_id from gridimage_daily where showday is null");
	
	//get the next $listlen entries of assignments
	$listlen=$CONF['potd_listlen'];#$CONF['potd_listlen']-1?
	$days=$listlen*$daysperimg;
	$image_list=$db->GetAssoc("select showday,gridimage_id,1 as assigned from gridimage_daily where to_days(showday)-to_days(now()) between $lower and $days");
	
	//get ordered list of pool images
	$pool=$db->GetCol("select gridimage_id from gridimage_daily inner join gridimage_search using (gridimage_id) where showday is null order by moderation_status desc,(abs(datediff(now(),imagetaken)) mod 365 div 14) asc,(vote_baysian > 3) desc,crc32(gridimage_id) limit $listlen");
	$coming_up=array();
	
	
	//fill in blanks
	$prev=-$daysperimg;
	for ($d=$lower; $d<$days; $d++)
	{
		$t=strtotime("+{$d} days");
		$showday=strftime("%Y-%m-%d", $t);
		if (isset($image_list[$showday])) {
			if ($d>=0) { $coming_up[$showday]=$image_list[$showday]; }
			$prev=$d;
		} else if ($d>=0 && $d-$prev >= $daysperimg) {
			if (count($pool))
			{
				$image=array_shift($pool);
				$coming_up[$showday]=array(
					'gridimage_id'=>$image,
					'pool'=>1);
			}
			else
			{
				//oh dear
				$coming_up[$showday]=array(
					'gridimage_id'=>0);
			}
			$prev=$d;
		} else if ($d==0 && $prev != -$daysperimg) {
			$t=strtotime("+{$prev} days");
			$showday=strftime("%Y-%m-%d", $t);
			$coming_up[$showday]=$image_list[$showday];
		}
	}
	ksort($coming_up);
	$smarty->assign_by_ref("coming_up", $coming_up);
	$smarty->assign_by_ref("pending", $pending);
	$smarty->assign_by_ref("pendingcount", count($pending));
}

$smarty->display($template,$cacheid);

	
?>
