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
require_once('geograph/mapmosaic.class.php');

init_session();

$smarty = new GeographPage;

$template='view.tpl';

$cacheid=0;


$image=new GridImage;

if (isset($_GET['id']))
{
	$image->loadFromId($_GET['id']);
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$ismoderator=$USER->hasPerm('moderator')?1:0;
	
	$cacheid="{$_GET['id']}_{$isowner}_{$ismoderator}";
	
	//is the image rejected? - only the owner and administrator should see it
	if ($image->moderation_status=='rejected')
	{
		if ($isowner||$ismoderator)
		{
			//ok, we'll let it lie...
		}
		else
		{
			//clear the image
			$image=new GridImage;
			$cacheid=0;
		}
	}
}

//do we have a valid image?
if ($image->isValid())
{
	$taken=$image->getFormattedTakenDate();
	
	//remove grid reference from title

	$image->title=trim(str_replace($image->grid_reference, '', $image->title));

	$smarty->assign('page_title', $image->title.":: OS grid {$image->grid_reference}");
	$smarty->assign('meta_description', $image->comment);
	$smarty->assign('image_taken', $taken);
	$smarty->assign('ismoderator', $USER->hasPerm('moderator')?1:0);
	$smarty->assign_by_ref('image', $image);
	
	$mosaic=new GeographMapMosaic;
	$smarty->assign('map_token', $mosaic->getGridSquareToken($image->grid_square));
}



$smarty->display($template, $cacheid);

	
?>
