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

# let the browser know what's coming
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"geograph.csv\"");

$db=NewADOConnection($GLOBALS['DSN']);

   echo "Id,Name,Grid Ref,Submitter,Image Class,easting,northing\n";

$recordSet = &$db->Execute("select gridimage_id,title,grid_reference,realname,imageclass,nateastings,natnorthings ".
	"from user ".
	"inner join gridimage using(user_id) ".
	"inner join gridsquare using(gridsquare_id) ".
	"where moderation_status in ('accepted','geograph')");
while (!$recordSet->EOF) 
{
	$image = $recordSet->fields;
	if (strpos($image['title'],',') !== FALSE || strpos($image['title'],'"') !== FALSE) 
	{
		$image['title'] = '"'.str_replace('"', '""', $image['title']).'"';
	}
	if (strpos($image['imageclass'],',') !== FALSE || strpos($image['imageclass'],'"') !== FALSE) 
	{
		$image['imageclass'] = '"'.str_replace('"', '""', $image['imageclass']).'"';
	}
	echo "{$image['gridimage_id']},{$image['title']},{$image['grid_reference']},{$image['realname']},{$image['imageclass']}".(($image['nateastings'])?",{$image['nateastings']},{$image['natnorthings']}":'')."\n";
	$recordSet->MoveNext();
	$i++;
}
$recordSet->Close(); 
	
?>
