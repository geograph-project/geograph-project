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



$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');   


//doing some moderating?
if (isset($_GET['gridimage_id']))
{
	//user may have an expired session, or playing silly buggers,
	//either way, we want to check for admin status on the session
	if ($USER->hasPerm("admin"))
	{
	
		$gridimage_id=$_GET['gridimage_id'];
		$status=($_GET['is_ok']=='true')?'accepted':'rejected';


		$image=new GridImage;
		if ($image->loadFromId($gridimage_id))
		{
			$image->setModerationStatus($status);
			echo "Image $status";
		}
		else
		{
			echo "FAIL";
		}
	}
	else
	{
		echo "NOT LOGGED IN";
	}
	
	
	
	exit;
}

///////////////////////////////
// administrators only!
$USER->mustHavePerm("admin");



$smarty = new GeographPage;

//lets find all unmoderated submissions
$images=array();
$i=0;
$recordSet = &$db->Execute("select gridimage.*,user.realname ".
	"from gridimage ".
	"inner join user using(user_id) ".
	"where moderation_status='pending'");
while (!$recordSet->EOF) 
{
	$images[$i]=new GridImage;
	$images[$i]->loadFromRecordset($recordSet);
	$recordSet->MoveNext();
	$i++;
}
$recordSet->Close(); 

$smarty->assign_by_ref('images', $images);		
$smarty->assign('imagecount', $i);
		
$smarty->display('admin_moderation.tpl');

	
?>
