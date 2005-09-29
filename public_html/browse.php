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
require_once('geograph/imagelist.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');

init_session();




$smarty = new GeographPage;
//$browser=new GridBrowser;
$square=new GridSquare;

$smarty->assign('prefixes', $square->getGridPrefixes());
$smarty->assign('kmlist', $square->getKMList());


//we can be passed a gridreference as gridsquare/northings/eastings 
//or just gridref. So lets initialise our grid square
$grid_given=false;
$grid_ok=false;



//set by grid components?
if (isset($_GET['setpos']))
{	
	$grid_given=true;
	$grid_ok=$square->setGridPos($_GET['gridsquare'], $_GET['eastings'], $_GET['northings']);

	//preserve inputs in smarty
	$smarty->assign('gridsquare', $square->gridsquare);
	$smarty->assign('eastings', $square->eastings);
	$smarty->assign('northings', $square->northings);
	$smarty->assign('gridref', $square->grid_reference);
	
}
//set by grid ref?
elseif (isset($_GET['gridref']) && strlen($_GET['gridref']))
{
	$grid_given=true;
	$grid_ok=$square->setByFullGridRef($_GET['gridref']);
	
	//preserve inputs in smarty
	
	if ($grid_ok)
	{
		$smarty->assign('gridref', $square->grid_reference);
		$smarty->assign('gridrefraw', stripslashes($_GET['gridref']));
		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
	}
	else
	{
		//preserve the input at least
		$smarty->assign('gridref', stripslashes($_GET['gridref']));
	
	}
	
}

//process grid reference
if ($grid_given)
{
	$square->rememberInSession();
	

	

	//now we see if the grid reference is actually available...
	if ($grid_ok)
	{
		//store details the browser manager has figured out
		$smarty->assign('showresult', 1);
		$smarty->assign('imagecount', $square->imagecount);
		
		//is this just a closest match?
		if (is_object($square->nearest))
		{
			$smarty->assign('nearest_distance', $square->nearest->distance);
			$smarty->assign('nearest_gridref', $square->nearest->grid_reference);
		
		}
		
		//otherwise, lets gether the info we need to display some thumbs
		if ($square->imagecount)
		{
			$images=$square->getImages();
			$smarty->assign_by_ref('images', $images);
		}

		//geotag the page	
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		list($lat,$long) = $conv->gridsquare_to_wgs84($square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);
	
		//get a token to show a suroudding geograph map
		$mosaic=new GeographMapMosaic;
		$smarty->assign('map_token', $mosaic->getGridSquareToken($square));
	
		$db=NewADOConnection($GLOBALS['DSN']);
		$sql='select topic_id,posts_count-1 as comments,CONCAT(\'Discussion on \',t.topic_title) as topic_title '.
			'from geobb_topics as t '.
			'where t.forum_id=5 and '.
			't.topic_title = \''.mysql_escape_string($square->grid_reference).'\' '.
			'order by t.topic_time desc';
		$topics=$db->GetAll($sql);
		if ($topics)
		{
			$news=array();
			
			foreach($topics as $idx=>$topic)
			{
				$firstpost=$db->GetRow("select * from geobb_posts where topic_id={$topic['topic_id']} order by post_time");
				$topics[$idx]['post_text']=GeographLinks(str_replace('<br>', '<br/>', $firstpost['post_text']));
				$topics[$idx]['poster_name']=$firstpost['poster_name'];
				$topics[$idx]['post_time']=$firstpost['post_time'];
				$totalcomments += $topics[$idx]['comments'] + 1;
			}
			$smarty->assign_by_ref('discuss', $topics);
			$smarty->assign('totalcomments', $totalcomments);	
		}
		
		
	
		
	}
	else
	{
		$smarty->assign('errormsg', $square->errormsg);
		
		
		
	}
}
else
{
	//no square specifed - populate with remembered values
	$smarty->assign('gridsquare', $_SESSION['gridsquare']);
	$smarty->assign('eastings', $_SESSION['eastings']);
	$smarty->assign('northings', $_SESSION['northings']);
	
}

//lets find some recent photos
new RecentImageList($smarty);

//lets add an overview map too
$overview=new GeographMapMosaic('overview');
$overview->assignToSmarty($smarty, 'overview');
if ($grid_ok)
	$smarty->assign('marker', $overview->getSquarePoint($square));



$smarty->display('browse.tpl');

	
?>
