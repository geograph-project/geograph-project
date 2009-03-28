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


$size = (isset($_GET['size']) && in_array($_GET['size'],array('full','med','small')))?$_GET['size']:'small';

$s = (isset($_GET['s']) && is_numeric($_GET['s']))?intval($_GET['s']):0;

if (!empty($_GET['rejected'])) {
$filename = "rejected-$size";
} else {
$filename = sprintf("geograph-%02d-$size",$s/10000);
}

if (file_exists("torrent/$filename.torrent")) {
	# let the browser know what's coming
	header("Content-type: application/x-bittorrent");
	header("Content-Disposition: attachment; filename=\"$filename.torrent\"");
	
	
	customCacheControl(filemtime("torrent/$filename.torrent"),$filename);
	customExpiresHeader(86400,true);
	
	readfile("torrent/$filename.torrent");
	exit;
}


dieUnderHighLoad();

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

require_once 'File/Bittorrent2/MakeTorrentFiles.php';

$MakeTorrent = new File_Bittorrent2_MakeTorrentFiles('');

$MakeTorrent->setAnnounce('http://cdn.geograph.org.uk:6969/announce');

$MakeTorrent->setComment('Photographs from Geograph British Isles, see http://'.$_SERVER['HTTP_HOST'].'/ Image Copyright respective owners, released under this Creative Commons Licence: 
http://creativecommons.org/licenses/by-sa/2.0/, see enclosed rdf file.');

$dir = $_SERVER['DOCUMENT_ROOT']."/photos/";

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

$rdf = fopen("photos/rdf/$filename.rdf",'w');

fwrite ($rdf,
'<rdf:RDF xmlns="http://web.resource.org/cc/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:georss="http://www.georss.org/georss/">
');

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

$db=NewADOConnection($GLOBALS['DSN']);
if (!empty($_GET['rejected'])) {
$recordSet = &$db->Execute(sprintf("select gi.*,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,user.realname as user_realname,user.nickname from gridimage gi inner join user using(user_id) where moderation_status = 'rejected'",$s,$s+10000));

} else {
$recordSet = &$db->Execute(sprintf("select gi.*,user.realname as user_realname from gridimage_search gi inner join user using (user_id) where gridimage_id between %d and %d",$s,$s+10000));
}

$files = array("rdf/$filename.rdf");
while (!$recordSet->EOF) 
{
	$image = $recordSet->fields;
	$file = getGeographFile($image['gridimage_id'],substr(md5($image['gridimage_id'].$image['user_id'].$CONF['photo_hashing_secret']), 0, 8),$size);
	if (file_exists($dir.'/'.$file)) {
		$files[] = $file;
		
fwrite ($rdf,
'<Work rdf:about="'.$file.'">
     <dc:title>'.$image['grid_reference'].' : '.htmlspecialchars2($image['title']).'</dc:title>
     <dc:identifier>http://'.$_SERVER['HTTP_HOST'].'/photo/'.$image['gridimage_id'].'</dc:identifier>
     <dc:creator><Agent>
	<dc:title>'.htmlspecialchars2($image['realname']).'</dc:title>
     </Agent></dc:creator>
     <dc:rights><Agent>
	<dc:title>'.htmlspecialchars2($image['credit_realname']?$image['user_realname']:$image['realname']).'</dc:title>
     </Agent></dc:rights>
     <dc:dateSubmitted>'.$image['submitted'].'</dc:dateSubmitted>
     <dc:format>image/jpeg</dc:format>
     <dc:type>http://purl.org/dc/dcmitype/StillImage</dc:type>
     <dc:publisher><Agent>
	<dc:title>'.$_SERVER['HTTP_HOST'].'</dc:title>
     </Agent></dc:publisher>
     <dc:subject>'.htmlspecialchars2($image['imageclass']).'</dc:subject>
     <georss:point>'.$image['wgs84_lat'].' '.$image['wgs84_long'].'</georss:point>
     '.(strpos($image->imagetaken,'-00')?'':'<dc:coverage>'.$image['imagetaken'].'</dc:coverage>').'
     <license rdf:resource="http://creativecommons.org/licenses/by-sa/2.0/" />
</Work>
');
	}
	$recordSet->MoveNext();
}
$recordSet->Close();

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#


fwrite ($rdf,
'
<License rdf:about="http://creativecommons.org/licenses/by-sa/2.0/">
   <permits rdf:resource="http://web.resource.org/cc/Reproduction" />
   <permits rdf:resource="http://web.resource.org/cc/Distribution" />
   <requires rdf:resource="http://web.resource.org/cc/Notice" />
   <requires rdf:resource="http://web.resource.org/cc/Attribution" />
   <permits rdf:resource="http://web.resource.org/cc/DerivativeWorks" />
   <requires rdf:resource="http://web.resource.org/cc/ShareAlike" />
</License>

</rdf:RDF>
');
fclose($rdf);

$MakeTorrent->addFiles($files,$dir);

$MakeTorrent->setName($filename);

$metainfo = $MakeTorrent->buildTorrent();

file_put_contents("torrent/$filename.torrent",$metainfo);

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#


	# let the browser know what's coming
	header("Content-type: application/x-bittorrent");
	header("Content-Disposition: attachment; filename=\"$filename.torrent\"");


customCacheControl(filemtime("torrent/$filename.torrent"),$filename);
customExpiresHeader(86400,true);
print $metainfo;
	
#	#	#	#	#	#	#	#	#	#	#	#	#	#	#


function getGeographFile($gridimage_id,$hash,$size) {

       $ab=sprintf("%02d", floor($gridimage_id/10000));
       $cd=sprintf("%02d", floor(($gridimage_id%10000)/100));
       $abcdef=sprintf("%06d", $gridimage_id);

       $file = "$ab/$cd/{$abcdef}_{$hash}";

       switch($size) {
               case 'full': return "$file.jpg"; break;
               case 'med': return "{$file}_213x160.jpg"; break;
               case 'small':
               default: return "{$file}_120x120.jpg";
       }
}

?>
