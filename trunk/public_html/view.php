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
$image=new GridImage;

if (isset($_GET['id']))
{
	$image->loadFromId($_GET['id']);
	
	//is the image rejected? - only the owner and administrator should see it
	if ($image->moderation_status=='rejected')
	{
		if (($image->user_id == $USER->user_id) ||
		    ($USER->hasPerm('admin')))
		{
			//ok, we'll let it lie...
		}
		else
		{
			//clear the image
			$image=new GridImage;
		}
	}
}

//do we have a valid image?
if ($image->isValid())
{
	$smarty->assign('page_title', $image->gridref);
	$smarty->assign_by_ref('image', $image);
}

$smarty->display('view.tpl');

	
?>
