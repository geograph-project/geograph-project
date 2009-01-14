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

$USER->mustHavePerm("admin");


$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'points';

set_time_limit(3600);


ob_start();
	print "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
?>
<kml xmlns="http://earth.google.com/kml/2.0">
<Document>
<name>Geograph Hectads - <?php echo $type; ?> :: Time Animation</name>


<?php
foreach (range(2005,date('Y')) as $year) {
	foreach (range(1,12) as $month) {
		if (!($year == 2005 && $month == 1) && !($year == date('Y') && $month > date('n')) ) {
			$thismonth = ($year == date('Y') && $month == date('n'));
			
			$whenb = sprintf("%04d-%02d",($month==1)?($year-1):$year,($month==1)?12:($month-1));
			$when  = sprintf("%04d-%02d",$year,$month);
	
			if (isset($_GET['build'])) {
				$url = "http://".$_SERVER['HTTP_HOST']."/_scripts/kml-hectad_coverage.php?type=$type&when=$when".($thismonth?'&over=1':'');
				$files[] = $url; 
				if (!isset($_GET['d'])) {
					file_get_contents($url);
				}
			}
			$url = "http://".$_SERVER['HTTP_HOST']."/kml/hectads-$type-$when.kmz";
		
		
?>
<NetworkLink>
	<name><?php echo $when; ?></name>
	<open>0</open>
	<Url>
		<href><?php echo $url; ?></href>
	</Url>
	<visibility>0</visibility>
	
	<TimeSpan>
	  <begin><?php echo $whenb; ?></begin>
	  <end><?php echo $when; ?></end>
	</TimeSpan>
</NetworkLink>
<?php
		}
	}
}
?>
</Document></kml><?php
		
$filedata = ob_get_contents();
ob_end_clean();

	print "<pre>";
	print_r($files);
	print "</pre>";

file_put_contents ( $_SERVER['DOCUMENT_ROOT']."/kml/hectads-$type-animation.kml", $filedata); 

print "wrote ".strlen($filedata);
print "<br/><br/><a href=\"/kml/hectads-$type-animation.kml\">Download KML</a>";

include("geograph/zip.class.php");

$zipfile = new zipfile();

// add the binary data stored in the string 'filedata'
$zipfile -> addFile($filedata, "doc.kml");   

$content =& $zipfile->file();

file_put_contents ( $_SERVER['DOCUMENT_ROOT']."/kml/hectads-$type-animation.kmz", $content);

print "<br/><br/><br/><br/>wrote ".strlen($content);
print "<br/><br/><a href=\"/kml/hectads-$type-animation.kmz\">Download KMZ</a>";




?>
