<?php
/**
 * $Project: GeoGraph $
 * $Id: export.csv.php 2984 2007-01-18 19:04:21Z barry $
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



$size = 'full';

$s = (isset($_GET['s']) && is_numeric($_GET['s']))?intval($_GET['s']):0;

$filename = sprintf("geograph-%02d-$size.torrent",$s/10000);

if (file_exists("torrent/$filename")) {
	# let the browser know what's coming
	header("Content-type: application/x-bittorrent");
	header("Content-Disposition: attachment; filename=\"$filename\"");
	
	
	customCacheControl(filemtime("torrent/$filename"),$filename);
	customExpiresHeader(86400,true);
	
	readfile("torrent/$filename");
	exit;
}


dieUnderHighLoad();

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

require_once 'File/Bittorrent2/MakeTorrentFiles.php';

$MakeTorrent = new File_Bittorrent2_MakeTorrentFiles('');

$MakeTorrent->setAnnounce('http://cdn.geograph.org.uk:6969/announce');

$MakeTorrent->setComment('Photographs from Geograph British Isles, see htpp://www.geograph.org.uk/ Image Copyright respective owners, released under this Creative Commons Licence: 
http://creativecommons.org/licenses/by-sa/2.0/');

$dir = $_SERVER['DOCUMENT_ROOT']."/photos/";

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

$db=NewADOConnection($GLOBALS['DSN']);

$recordSet = &$db->Execute(sprintf("select gridimage_id,user_id from gridimage_search gi where gridimage_id between %d and %d",$s,$s+10000));

$files = array();
while (!$recordSet->EOF) 
{
	$image = $recordSet->fields;
	$i = getGeographFile($image['gridimage_id'],substr(md5($image['gridimage_id'].$image['user_id'].$CONF['photo_hashing_secret']), 0, 8),$size);
	if (file_exists($dir.'/'.$i[0].'/'.$i[1])) {
		$files[] = implode("/",$i);
	}
	$recordSet->MoveNext();
}
$recordSet->Close();

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

$MakeTorrent->addFiles($files,$dir);

$metainfo = $MakeTorrent->buildTorrent();

file_put_contents("torrent/$filename",$metainfo);

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#


	# let the browser know what's coming
	header("Content-type: application/x-bittorrent");
	header("Content-Disposition: attachment; filename=\"$filename\"");


customCacheControl(filemtime("torrent/$filename"),$filename);
customExpiresHeader(86400,true);
print $metainfo;
	
#	#	#	#	#	#	#	#	#	#	#	#	#	#	#


function getGeographFile($gridimage_id,$hash,$size) {

       $ab=sprintf("%02d", floor($gridimage_id/10000));
       $cd=sprintf("%02d", floor(($gridimage_id%10000)/100));
       $abcdef=sprintf("%06d", $gridimage_id);
       $fullpath="$ab/$cd";
       
       $file = "{$abcdef}_{$hash}";

       switch($size) {
               case 'full': return array($fullpath, "$file.jpg"); break;
               case 'med': return array($fullpath, "{$file}_213x160.jpg"); break;
               case 'small':
               default: return array($fullpath, "{$file}_120x120.jpg");
       }
}	

?>
