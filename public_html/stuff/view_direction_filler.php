<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
	require_once('geograph/event.class.php');
init_session();


$smarty = new GeographPage;

$template='statistics_table.tpl';

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):$USER->user_id;

$cacheid='viewdirection.'.$u;

$smarty->caching = 2;

if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, $cacheid);

if (!$smarty->is_cached($template, $cacheid))
{

	$db = NewADOConnection($GLOBALS['DSN']);
	$table = array();
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$images = $db->getAll("
	SELECT
		gridimage_id
	FROM
		gridimage 
	WHERE
		user_id = $u
	AND 
		moderation_status in ('geograph','accepted')
	AND
		viewpoint_eastings > 0 AND viewpoint_northings > 0
	AND
		viewpoint_eastings != nateastings AND viewpoint_northings != natnorthings
	LIMIT
		100
	");
	foreach ($images as $i => $row) {
		$image=& new GridImage();
		$image->LoadFromId($row['gridimage_id']);	
		$image->compact();
		
		$line = array();
		$line['Square'] = $image->grid_reference;
		$line['Title'] = "<a href=\"/photo/{$row['gridimage_id']}\">{$image->title}</a>";
		
		$submore4 = ($image->grid_square->nateastings > 0);
		
		$line['Photographer'] = $image->getPhotographerGridref();
		$line['Subject'] = $image->getSubjectGridref();
		
		$promore4 = $image->photographer_gridref_precision < 1000;
		
		//mimic the behaviour doen by getNatEastings to place the location in the center of the 1km square
		if (!$promore4) {
			$image->viewpoint_eastings += 500;
			$image->viewpoint_northings += 500;
		}
		
		$dist = sqrt (pow($image->grid_square->nateastings - $image->viewpoint_eastings ,2) + 
			pow($image->grid_square->natnorthings - $image->viewpoint_northings ,2) );
		
		
		
		$line['Distance'] = sprintf("%0.3f",$dist/1000);
		#$line['promore4'] = $promore4;
		#$line['submore4'] = $submore4;
		
		$angle = rad2deg(atan2( $image->grid_square->nateastings - $image->viewpoint_eastings,
			$image->grid_square->natnorthings - $image->viewpoint_northings ));
		
		if ($angle < 0)
			$angle+=360;
		
		$jump = 360/16; $jump2 = 360/32;
		
		$q = round($angle/$jump)*$jump;
		
		
		$s = ($q%90==0)?strtoupper(heading_string($q)):ucwords(heading_string($q));
		$direction = sprintf('%s : %03d deg (%03d > %03d)',
			str_pad($s,16,' '),
			$q,
			($q == 0?$q+360-$jump2:$q-$jump2),
			$q+$jump2);
		
		$angle = sprintf('%.1f',$angle);
		if (!$dist) {
			$line['Direction'] = "<small style=\"color:gray\">Same Location</small>";
			$q = '-';
		} elseif ($dist >= 1000) {
			$line['Direction'] = "<b>$s</b> <nobr>[$angle]</nobr>"; #"Wide:".$angle."<br>".
			$q = floor($q);
		} elseif ($promore4 && $submore4) {
			$line['Direction'] = "<b>$s</b> <nobr>[$angle]</nobr>"; #"Detailed:".$angle."<br>".
			$q = floor($q);
		} else {
			$line['Direction'] = "<small style=\"color:gray\">".(($submore4)?'Photographer':'Subject').' Location Not Accurate Enough</small>';
			$q = '-';
		}
		
		$line['Result'] = $q;
		$line['Manually Specified'] = ($image->view_direction> -1)?$image->view_direction:'-';
		$table[] = $line;
	}
	
	$smarty->assign_by_ref('table', $table);

	$smarty->assign("h2title",'Listing images with Photographer position specified (and different to Subject)');
	$smarty->assign("total",count($table));


}

$smarty->display($template,$cacheid);

	
?>
