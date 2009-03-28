<?php
/**
 * $Project: GeoGraph $
 * $Id: most_geographed.php,v 1.12 2005/11/03 16:07:41 barryhunter Exp $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();

require_once('geograph/conversions.class.php');
$conv = new Conversions;

$db = NewADOConnection($GLOBALS['DSN']);

$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'points';

$when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';

set_time_limit(3600);

$hectads = array();


$filename = $_SERVER['DOCUMENT_ROOT']."/kml/hectads-$type".($when?"-$when":'').".kml";

if (file_exists($filename) && empty($_GET['over']))
	die("done");
	
	foreach ($CONF['references'] as $ri => $rname) {
		$letterlength = $CONF['gridpreflen'][$ri];
			
		$origin = $db->CacheGetRow(100*24*3600,"select origin_x,origin_y from gridprefix where reference_index=$ri and origin_x > 0 order by origin_x,origin_y limit 1");
		
		if ($type == 'points' && !$when) {
			$most = $db->GetAll("select 
			x,y,
			concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) as tenk_square,
			sum(has_geographs) as image_count,
			sum(percent_land >0) as land_count
			from gridsquare 
			where reference_index = $ri 
			group by tenk_square 
			having land_count > 0
			order by null");
		} else {
			if ($type == 'points') {
				$sql_column = "sum(moderation_status='geograph' and ftf=1)"; //as gridimage_search
				$heading = "Images";	
			} elseif ($type == 'images') {
				$sql_column = "count(*)"; //as gridimage_search
				$heading = "Images";
			} elseif ($type == 'geographs') {
				$sql_column = "sum(moderation_status='geograph')";
				$heading = "New<br/>Geographs";
				$desc = "'geograph' images submitted";
			} 
			$andwhere = '';
			if ($when) {
				if (strlen($when) == 7) {
					$andwhere = " and submitted < DATE_ADD('$when-01',interval 1 month)";
					$whenb = $db->getOne("select SUBSTRING(DATE_SUB('$when-01',interval 1 month),1,7)");
				} elseif (strlen($when) == 4) {
					$whenb = $when - 1;
					$andwhere = " and submitted < DATE_ADD('$when-01-01',interval 1 year)";
				} else {
					$whenb = $when;
					$andwhere = " and submitted < '$when'";
				}
			}
			$most = $db->GetAll("select 
			x,y,
			concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) as tenk_square,
			$sql_column as image_count
			from gridimage_search
			where reference_index = $ri $andwhere
			group by tenk_square
			order by null");
		}
		
		foreach($most as $id=>$entry) 
		{
			$most[$id]['x'] = ( intval(($most[$id]['x'] - $origin['origin_x'])/10)*10 ) +  $origin['origin_x'];
			$most[$id]['y'] = ( intval(($most[$id]['y'] - $origin['origin_y'])/10)*10 ) +  $origin['origin_y'];
			$most[$id]['reference_index'] = $ri;
		}	
		$hectads = array_merge($hectads,$most);
	}

	if (isset($_GET['stat'])) {
		$statt = array();
		foreach($hectads as $id=>$entry) {
			$statt[$entry['image_count']]++;
			if ($entry['image_count'] && $entry['image_count'] < 10)
				$totla++;
		}
		ksort($statt);
		
		print "$totla<pre>";
		print_r($statt);
		exit;
	}


#header("Content-type: application/vnd.google-earth.kml");
ob_start();
	print "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
?>
<kml xmlns="http://earth.google.com/kml/2.0">
<Document>
<name>Geograph Hectads - <?php echo $type; if ($when) echo " - upto $when"; ?></name>
<Style id="Style1">
	<IconStyle>
		<scale>0</scale>
	</IconStyle>
</Style>
<Folder>
<name>Labels</name>
<visibility>0</visibility>

<?php
	foreach ($hectads as $square) {
	
		list($lat,$long) = $conv->internal_to_wgs84($square['x']+5,$square['y']+5,$square['reference_index']);
		
		$height = $square['image_count'] * 200;
?>
  <Placemark>
  	<name><?php echo $square['tenk_square']; ?></name>
    <visibility>0</visibility>
    <styleUrl>#Style1</styleUrl>
	<Point>
		<coordinates><?php echo "$long,$lat,$height"; ?></coordinates>
		<altitudeMode>relativeToGround</altitudeMode>
	</Point>
  </Placemark>
<?php
	}
?>
<?php if ($when) { ?>
	<TimeSpan>
	  <begin><?php echo $whenb; ?></begin>
	  <end><?php echo $when; ?></end>
	</TimeSpan>
<?php } ?>
</Folder>
<Placemark>
<name>Bars</name>
<visibility>1</visibility>
<?php if ($when) { ?>
	<TimeSpan>
	  <begin><?php echo $whenb; ?></begin>
	  <end><?php echo $when; ?></end>
	</TimeSpan>
<?php } ?>
<MultiGeometry>
<?php

	$done = array();
	function getll($x,$y,$ri) {
		global $done,$conv;
		$bit = array();
		if ($done["$x-$y"]) {
			$bit = $done["$x-$y"];
		} else {
			list($bit['lat'],$bit['long']) = $conv->internal_to_wgs84($x,$y,$ri);
		}
		return $bit;
	}

	foreach ($hectads as $square) {
	
		$bits = array();
		
		$bits[] = getll($square['x'],$square['y'],$square['reference_index']);
		$bits[] = getll($square['x']+10,$square['y'],$square['reference_index']);
		$bits[] = getll($square['x']+10,$square['y']+10,$square['reference_index']);
		$bits[] = getll($square['x'],$square['y']+10,$square['reference_index']);
		
		$height = $square['image_count'] * 200;
  ?>
		<Polygon>
			<altitudeMode>relativeToGround</altitudeMode>
			<extrude>1</extrude>
			<tessellate>1</tessellate>
			<outerBoundaryIs>
				<LinearRing>
					<coordinates>
					<?php
					foreach ($bits as $bit) {
						echo "{$bit['long']},{$bit['lat']},$height ";
					}
					$bit = $bits[0];
					echo "{$bit['long']},{$bit['lat']},$height "; 
					?>
					</coordinates>
				</LinearRing>
			</outerBoundaryIs>
		</Polygon>
  <?php
  	}
?>
</MultiGeometry>
</Placemark>
</Document></kml><?php
		
$filedata = ob_get_contents();
ob_end_clean();

file_put_contents ( $filename, $filedata); 

$type = "$type".($when?"-$when":'');

print "wrote ".strlen($filedata);
print "<br/><br/><a href=\"/kml/hectads-$type.kml\">Download KML</a>";


include("geograph/zip.class.php");

$zipfile = new zipfile();

// add the binary data stored in the string 'filedata'
$zipfile -> addFile($filedata, "doc.kml");

$content =& $zipfile->file();

file_put_contents ( $_SERVER['DOCUMENT_ROOT']."/kml/hectads-$type.kmz", $content);

print "<br/><br/><br/><br/>wrote ".strlen($content);
print "<br/><br/><a href=\"/kml/hectads-$type.kmz\">Download KMZ</a>";


?>
