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
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;

if (isset($_GET['id']))  {
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	$image=new GridImage;
	
	$ok = $image->loadFromId($_GET['id'],true);

	if ($ok) {
		header("Content-type: application/vnd.google-earth.kml+xml");
		header("Content-Disposition: attachment; filename=\"Geograph.kml\"");
		header("Cache-Control: Public");
		header("Expires: ".date("D, d M Y H:i:s",mktime(0,0,0,date('m'),date('d')+14,date('Y')) )." GMT");
		
		print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?><kml xmlns="http://earth.google.com/kml/2.0">
	<Placemark>
		<description><![CDATA[<? echo $image->comment; ?>]]></description>
		<name><![CDATA[<? echo $image->grid_reference." : ".$image->title; ?>]]></name>
		<visibility>1</visibility>
		<Point>
			<coordinates><? echo $image->wgs84_long.",".$image->wgs84_lat; ?>,25</coordinates>
		</Point>
		<styleUrl>root://styleMaps#default?iconId=0x307</styleUrl>
		<Style>
			<icon><? echo "http://".$_SERVER['HTTP_HOST'].$image->getThumbnail(120,120,true); ?></icon>
		</Style>
	</Placemark>
</kml><?		
		exit;
	} else {
		
	}
}		

$template='kml.tpl';
	
	if (isset($_REQUEST['i']) && $i = intval($_REQUEST['i'])) {
		$pg = $_REQUEST['page'];
		if ($pg == '' or $pg < 1) {$pg = 1;}

		$engine = new SearchEngine($_REQUEST['i']);
		
		
		if (isset($_POST['submit'])) {
			$simple = $_POST['simple'];
			if ($_POST['type'] == 'view') {
				$url = "http://{$_SERVER['HTTP_HOST']}/earth.php?i=$i&simple=$simple";
			} else {
				$url = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?format=KML&i=$i&simple=$simple&page=$pg";
			}
			if ($_POST['type'] == 'static') {
				header("Status:302 Found");
				header("Location:$url");
				$url = str_replace('&','&amp;',$url);
				print "<a href=\"$url\">Open KML</a>";
				exit;
			} else {
				$url = str_replace('&','&amp;',$url);
				if ($_POST['type'] == 'time') {
					$view = "<refreshMode>onInterval</refreshMode>\n<refreshInterval>{$_POST['refresh']}</refreshInterval>";
				} else {
					$view = "<viewRefreshMode>onStop</viewRefreshMode>\n<viewRefreshTime>4</viewRefreshTime>";
				}
				header("Content-type: application/vnd.google-earth.kml+xml");
				header("Content-Disposition: attachment; filename=\"Geograph.kml\"");
				header("Cache-Control: Public");
				header("Expires: ".date("D, d M Y H:i:s",mktime(0,0,0,date('m'),date('d')+14,date('Y')) )." GMT");
		

				print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?><kml xmlns="http://earth.google.com/kml/2.0">
<NetworkLink>
<name>Geograph NetworkLink</name>
<description><![CDATA[Images<i><? echo $engine->criteria->searchdesc; ?></i>]]></description>
<open>0</open>
<Url>
<href><? echo $url; ?></href>
<? echo $view; ?>
</Url>
<visibility>0</visibility>
</NetworkLink>
</kml><?		
				exit;
			}		
		} else {
			$engine->countOnly = true;
			$smarty->assign('querytime', $engine->Execute($pg)); 
			
			$smarty->assign('i', $i);
			$smarty->assign('currentPage', $pg);
			$smarty->assign_by_ref('engine', $engine);
		
		}
		
	} else {
		$is = array(1522=>'Recent Submissions',
			46131 => 'Selection of Photos across the British Isles',
			-1 => 'Your Pictures',
			25680 => 'one random image from each gridsquare, in Great Britain',
			25681 => 'one random image from each gridsquare, in Ireland',
			25677 => 'one random image from every user',
			25678 => 'one random image from each category',
			46002 => 'Random Images',
			44622 => 'Moderated in the last 24 Hours',
		);
		$smarty->assign_by_ref('is', $is);
		$smarty->assign('currentPage', 1);
	}
		




$smarty->display($template, $cacheid);

	
?>
