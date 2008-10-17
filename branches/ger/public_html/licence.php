<?php
/**
 * $Project: GeoGraph $
 * $Id: editimage.php 3310 2007-04-26 21:41:21Z barry $
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
require_once('geograph/gridimagetroubleticket.class.php');

init_session();

$smarty = new GeographPage;

//you must be logged in to request changes
$USER->mustHavePerm("basic");


$template='licence.tpl';
$cacheid='';


$image=new GridImage;	

if (isset($_REQUEST['id']))
{
	$image->loadFromId($_REQUEST['id']);
	$isowner=($image->user_id==$USER->user_id)?1:0;
	$isadmin=$USER->hasPerm('ticketmod')?1:0;

	if ($image->isValid())
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
		
		
		if (isset($_POST['pattrib'])) {
			if ($_POST['pattrib'] == 'other') {
				$image->setCredit(stripslashes($_POST['pattrib_name']));
			} elseif ($_POST['pattrib'] == 'self') {
				$image->setCredit('');
			}
			if (!empty($_POST['pattrib_default'])) {
				$USER->setCreditDefault(($_POST['pattrib'] == 'other')?stripslashes($_POST['pattrib_name']):'');
			}
		
			//clear any caches involving this photo
			$ab=floor($image->gridimage_id/10000);
			$smarty->clear_cache(null, "img$ab|{$image->gridimage_id}");

			//clear user specific stuff like profile page
			$smarty->clear_cache(null, "user{$image->user_id}");
		
			header("Location: /photo/{$_REQUEST['id']}");
			exit;
		}

		//do our thing!
		$smarty->assign('page_title', $image->grid_reference);
		$smarty->assign_by_ref('image', $image);
		$smarty->assign_by_ref('isowner', $isowner);
		$smarty->assign_by_ref('isadmin', $isadmin);


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
