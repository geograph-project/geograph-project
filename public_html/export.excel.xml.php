<?php
/**
 * $Project: GeoGraph $
 * $Id: export.csv.php 2805 2006-12-30 12:03:55Z barry $
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

  $smarty = new GeographPage;
  dieUnderHighLoad();

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

include('geograph/export.inc.php');

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#

# let the browser know what's coming
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"geograph.xml\"");

print "<?xml version=\"1.0\"?>\n";
print "<?mso-application progid=\"Excel.Sheet\"?>\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel"/>
 <Styles>
  <Style ss:ID="sHy" ss:Name="Hyperlink">
   <Font ss:Color="#0000FF" ss:Underline="Single"/>
  </Style>
  <Style ss:ID="sDt">
   <NumberFormat ss:Format="Short Date"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="geograph">
  <Table>
   <Row>
<?php
	foreach (explode(',',$csvhead) as $col) {
		print "<Cell><Data ss:Type=\"String\">$col</Data></Cell>";
	}
	print "</Row>\n";

$counter = -1;
while (!$recordSet->EOF) 
{
	print "<Row>\n";
	$image = $recordSet->fields;
	$image['title'] = combineTexts($image['title'], $image['title2']);

	print "<Cell ss:StyleID=\"sHy\" ss:HRef=\"http://{$_SERVER['HTTP_HOST']}/photo/{$image['gridimage_id']}\"><Data ss:Type=\"Number\">{$image['gridimage_id']}</Data></Cell>\n";
	print "<Cell><Data ss:Type=\"String\">{$image['title']}</Data></Cell>";
	print "<Cell><Data ss:Type=\"String\">{$image['grid_reference']}</Data></Cell>";
	print "<Cell><Data ss:Type=\"String\">{$image['realname']}</Data></Cell>";
	print "<Cell><Data ss:Type=\"String\">{$image['imageclass']}</Data></Cell>";

	if (!empty($_GET['thumb'])) {
		$gridimage->fastInit($image);
		print "<Cell><Data ss:Type=\"String\">".$gridimage->getThumbnail(120,120,true)."</Data></Cell>";
	}
	if (!empty($_GET['en'])) {
		print "<Cell><Data ss:Type=\"Number\">{$image['nateastings']}</Data></Cell>\n";
		print "<Cell><Data ss:Type=\"Number\">{$image['natnorthings']}</Data></Cell>\n";
		print "<Cell><Data ss:Type=\"Number\">{$image['natgrlen']}</Data></Cell>\n";
		if (!empty($_GET['ppos'])) {
			print "<Cell><Data ss:Type=\"Number\">{$image['viewpoint_eastings']}</Data></Cell>\n";
			print "<Cell><Data ss:Type=\"Number\">{$image['viewpoint_northings']}</Data></Cell>\n";
			print "<Cell><Data ss:Type=\"Number\">{$image['viewpoint_grlen']}</Data></Cell>\n";
		}
	} elseif (!empty($_GET['ll'])) {
		print "<Cell><Data ss:Type=\"Number\">{$image['wgs84_lat']}</Data></Cell>\n";
		print "<Cell><Data ss:Type=\"Number\">{$image['wgs84_long']}</Data></Cell>\n";
	}
	if (!empty($_GET['taken'])) {
		if (strpos($image['imagetaken'],'-00') === FALSE) 
			print "<Cell ss:StyleID=\"sDt\"><Data ss:Type=\"DateTime\">{$image['imagetaken']}T00:00:00.000</Data></Cell>\n";
		else 
			print "<Cell><Data ss:Type=\"String\">{$image['imagetaken']}</Data></Cell>\n";
	}
	if (!empty($_GET['dir']))
		print "<Cell><Data ss:Type=\"Number\">{$image['view_direction']}</Data></Cell>\n";
	
	echo "</Row>\n";
	$recordSet->MoveNext();
	$counter++;
}
$recordSet->Close();

?>
  </Table>
 </Worksheet>
</Workbook><?php

#	#	#	#	#	#	#	#	#	#	#	#	#	#	#
	
//todo
//if (isset($_GET['since']) && preg_match("/^\d+-\d+-\d+$/",$_GET['since']) ) {
// or if (isset($_GET['last']) && preg_match("/^\d+ \w+$/",$_GET['last']) ) {
// ... find all rejected (at first glance think only need ones submitted BEFORE but moderated AFTER, as ones submitted after wont be included!) - either way shouldnt harm to include them anyway!
	
$sql = "UPDATE apikeys SET accesses=accesses+1, records=records+$counter,last_use = NOW() WHERE `apikey` = '{$_GET['key']}'";

$db->Execute($sql);	

?>
