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

$db = GeographDatabaseConnection(true);

$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'points';

$when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';

$scale = (isset($_GET['scale']) && preg_match('/^\w+$/' , $_GET['scale']))?$_GET['scale']:'scale';


set_time_limit(3600);

$hectads = $hectadsgray = $hectadsred = $hectadsgreen = array();


$filename = $_SERVER['DOCUMENT_ROOT']."/kml/hectads-$type".($when?"-$when":'').($scale?"-$scale":'')."-color.kml";

if (file_exists($filename) && empty($_GET['over']))
	die("done ".basename($filename));


	foreach (array(1,2) as $ri) {
		$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?

		$origin = $db->CacheGetRow(100*24*3600,"select origin_x,origin_y from gridprefix where reference_index=$ri and origin_x > 0 order by origin_x,origin_y limit 1");

		if (!$when) {
			if ($type == 'points') {
				$sql_column = 'geosquares';
				$heading="Points";
			} elseif ($type == 'images') {
				$sql_column = "images";
				$heading = "Images";
			} elseif ($type == 'users') {
				$sql_column = "users";
				$heading = "Contributors";
			} elseif ($type == 'geographs') {
				$sql_column = "geographs";
				$heading = "New<br/>Geographs";
				$desc = "'geograph' images submitted";
			} elseif ($type == 'percentage') {
				$sql_column = "geosquares/landsquares*100";
				$heading = "Percentage Geographed";
			} elseif ($type == 'recent') {
				$sql_column = "recentsquares/landsquares*100";
				$heading = "Recently Geographed";
			}

			if ($scale == 'sqrt')		$sql_column = "sqrt($sql_column)";
			elseif ($scale == 'ln')		$sql_column = "ln($sql_column)*10";
			elseif ($scale == 'log10')	$sql_column = "log10($sql_column)*10";

			$most = $db->GetAll("select x,y,hectad as tenk_square, $sql_column as image_count
			from hectad_stat
			where reference_index = $ri
			and landsquares > 0");
		} else {
			if ($type == 'points') {
				$sql_column = "sum(moderation_status='geograph' and ftf=1)"; //as gridimage_search
				$heading = "Points";
			} elseif ($type == 'images') {
				$sql_column = "count(*)"; //as gridimage_search
				$heading = "Images";
			} elseif ($type == 'users') {
				$sql_column = "count(distinct user_id)";
				$heading = "Contributors";
			} elseif ($type == 'geographs') {
				$sql_column = "sum(moderation_status='geograph')";
				$heading = "New<br/>Geographs";
				$desc = "'geograph' images submitted";
			}

			if ($scale == 'sqrt')		$sql_column = "sqrt($sql_column)";
			elseif ($scale == 'ln')		$sql_column = "ln($sql_column)*10";
			elseif ($scale == 'log10')	$sql_column = "log10($sql_column)*10";

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
		$grid = array();
		foreach($most as $id=>$entry)
		{
			$most[$id]['x'] = ( intval(($most[$id]['x'] - $origin['origin_x'])/10)*10 ) +  $origin['origin_x'];
			$most[$id]['y'] = ( intval(($most[$id]['y'] - $origin['origin_y'])/10)*10 ) +  $origin['origin_y'];
			$most[$id]['reference_index'] = $ri;
			$grid[$most[$id]['x']][$most[$id]['y']] = $entry['image_count'];
		}
		foreach($most as $id=>$entry) {
			$values = array();
			foreach(range($entry['x']-10,$entry['x']+10,10) as $x)
				foreach(range($entry['y']-10,$entry['y']+10,10) as $y)
					if (isset($grid[$x][$y]))
						array_push($values,$grid[$x][$y]);

			$avg = array_sum($values)/count($values); //should never be zero values, as will include 'self'
			$delta = $entry['image_count']/10; //=10%

			if (abs($avg-$entry['image_count']) > $delta) {
				if ($avg < $entry['image_count'])
					$hectadsred[] = $most[$id];
				else //if ($avg > $entry['image_count'])
					$hectadsgreen[] = $most[$id];

			} else {
				$hectadsgray[] = $most[$id];
			}
		}
	}

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

#header("Content-type: application/vnd.google-earth.kml");
ob_start();
	print "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
?>
<kml xmlns="http://earth.google.com/kml/2.0">
<Document>
<name>Geograph Hectads - <? echo $type; if ($when) echo " - upto $when"; ?></name>
<Style id="Style1">
	<IconStyle>
		<scale>0</scale>
	</IconStyle>
</Style>
<Style id="StyleRed">
	<PolyStyle>
		<color>ff7f55ff</color>
	</PolyStyle>
</Style>
<Style id="StyleGreen">
	<PolyStyle>
		<color>ff7fff55</color>
	</PolyStyle>
</Style>

<Placemark>
<name>Bars Gray</name>
<visibility>1</visibility>
<? if ($when) { ?>
	<TimeSpan>
	  <begin><? echo $whenb; ?></begin>
	  <end><? echo $when; ?></end>
	</TimeSpan>
<? } ?>
<MultiGeometry>
<?

	foreach ($hectadsgray as $square) {

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
					<?
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
  <?
  	}
?>
</MultiGeometry>
</Placemark>

<Placemark>
<name>Bars Red</name>
<visibility>1</visibility>
<? if ($when) { ?>
	<TimeSpan>
	  <begin><? echo $whenb; ?></begin>
	  <end><? echo $when; ?></end>
	</TimeSpan>
<? } ?>
<styleUrl>#StyleRed</styleUrl>

<MultiGeometry>
<?

	foreach ($hectadsred as $square) {

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
					<?
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
  <?
  	}
?>
</MultiGeometry>
</Placemark>

<Placemark>
<name>Bars Green</name>
<visibility>1</visibility>
<? if ($when) { ?>
	<TimeSpan>
	  <begin><? echo $whenb; ?></begin>
	  <end><? echo $when; ?></end>
	</TimeSpan>
<? } ?>
<styleUrl>#StyleGreen</styleUrl>

<MultiGeometry>
<?

	foreach ($hectadsgreen as $square) {

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
					<?
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
  <?
  	}
?>
</MultiGeometry>
</Placemark>



</Document></kml><?

$filedata = ob_get_contents();
ob_end_clean();

file_put_contents ( $filename, $filedata);

$type = "$type".($when?"-$when":'');
$type = "$type".($scale?"-$scale":'');
$type = "$type"."-color";

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


