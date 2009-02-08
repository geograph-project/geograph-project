<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();

if (empty($_GET)) {
	$template = 'touchgraph.tpl';
	
	$smarty = new GeographPage;
	$smarty->display($template);

	exit;
} 

$d=(isset($_REQUEST['d']))?min(30,intval(stripslashes($_REQUEST['d']))):1;
			

$square=new GridSquare;
//set by grid components?
if (isset($_GET['p']))
{	
	$grid_given=true;
	//p=900y + (900-x);
	$p = intval($_GET['p']);
	$x = ($p % 900);
	$y = ($p - $x) / 900;
	$x = 900 - $x;
	$grid_ok=$square->loadFromPosition($x, $y, true);
	$grid_given=true;
	$smarty->assign('gridrefraw', $square->grid_reference);
} elseif (isset($_REQUEST['gridref'])) {
	$grid_ok=$square->setByFullGridRef($_REQUEST['gridref']);
}

if ($grid_ok)
{
	$x = $square->x;
	$y = $square->y;
	
	$sql_where = '';

	$left=$x-$d;
	$right=$x+$d;
	$top=$y+$d;
	$bottom=$y-$d;

	$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

	$sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";

	if (!empty($_GET['s'])) {
		//shame cant use dist_sqd in the next line!
		$sql_where .= " and ((gi.x - $x) * (gi.x - $x) + (gi.y - $y) * (gi.y - $y)) < ".($d*$d);
	}

	$sql = "SELECT gridimage_id,grid_reference,title,realname,user_id,imageclass,imagetaken,label
	FROM gridimage_search gi
	LEFT JOIN gridimage_group USING (gridimage_id)
	WHERE $sql_where
	GROUP BY gridimage_id 
	ORDER BY NULL
	LIMIT 150"; ##limt just to make sure


	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$photos = $db->getAssoc($sql);

} elseif (!empty($_GET['user_id'])) {
	$u = intval($_GET['user_id']);
	
	$sql_where = "user_id = $u";
	
	$sql = "SELECT gridimage_id,grid_reference,title,realname,user_id,imageclass,imagetaken,label
	FROM gridimage_search gi
	LEFT JOIN gridimage_group USING (gridimage_id)
	WHERE $sql_where
	GROUP BY gridimage_id 
	ORDER BY NULL"; ##limt just to make sure

	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	
	$photos = $db->getAssoc($sql);
}

###################

$inc = $_GET['inc'];

$relations = array();
foreach ($photos as $id => $row) {
	if ($inc['imageclass']) 
		$relations[] = array($id,$row['imageclass'],'Category');
	if ($inc['realname']) 
		$relations[] = array($id,$row['realname'],'Contributor');
	if ($inc['year']) 
		$relations[] = array($id,substr($row['imagetaken'],0,4),'Year');
	if ($inc['month']) 
		$relations[] = array($id,substr($row['imagetaken'],0,7),'Month');
	if ($inc['imagetaken'] && strpos($row['imagetaken'],'-00') === FALSE)
		$relations[] = array($id,$row['imagetaken'],'Taken');
	if ($inc['label'] && !empty($row['label']))
		$relations[] = array($id,$row['label'],'Label');
}

###################

$sxe = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><TouchGraph_Navigator Version="1.0"></TouchGraph_Navigator>');

###########
$sxe_config = $sxe->addChild('Configuration');
	$sxe_names = $sxe_config->addChild('Names');
	$sxe_names->addChild('ItemName','Photo');
	$sxe_names->addChild('ItemIdName','Photo');
	$sxe_names->addChild('GroupName','Feature');
	
	$sxe_att = $sxe_config->addChild('Attributes');
	$sxe_iatt = $sxe_att->addChild('ItemAttributes');
	$att = $sxe_iatt->addChild('Attribute');
		$att->addAttribute('name', 'Title');
		$att->addAttribute('type', 'Text');
	$att = $sxe_iatt->addChild('Attribute');
		$att->addAttribute('name', 'Taken');
		$att->addAttribute('type', 'Text');
	$att = $sxe_iatt->addChild('Attribute');
		$att->addAttribute('name', 'URL');
		$att->addAttribute('type', 'URI');
	$att = $sxe_iatt->addChild('Attribute');
		$att->addAttribute('name', 'Thumbnail');
		$att->addAttribute('type', 'Image');
	$att = $sxe_iatt->addChild('Attribute');
		$att->addAttribute('name', 'Contributor');
		$att->addAttribute('type', 'Text');
			
/*		$att = $sxe_iatt->addChild('Attribute');
			$att->addAttribute('name', 'Taken');
			$att->addAttribute('type', 'Category');
		$att = $sxe_iatt->addChild('Attribute');
			$att->addAttribute('name', 'Contributor');
			$att->addAttribute('type', 'Category');
		$att = $sxe_iatt->addChild('Attribute');
			$att->addAttribute('name', 'Category');
			$att->addAttribute('type', 'Category');*/

	$sxe_gatt = $sxe_att->addChild('GroupRelationAttributes');
	$att = $sxe_gatt->addChild('Attribute');
		$att->addAttribute('name', 'Type');
		$att->addAttribute('type', 'Text');

###########
$sxe_data = $sxe->addChild('Data');

	$sxe_items = $sxe_data->addChild('Items');
	foreach ($photos as $id => $row) {
		$sxe_item  = $sxe_items->addChild('Item');
		$sxe_item->addAttribute('id', $id);
		$sxe_item->addChild('Attribute', $row['title'])->addAttribute('name', 'Title');
		$sxe_item->addChild('Attribute', "http://".$_SERVER['HTTP_HOST']."/photo/".$id)->addAttribute('name', 'URL');
		$sxe_item->addChild('Attribute', $row['imagetaken'])->addAttribute('name', 'Taken');
		$sxe_item->addChild('Attribute', $row['realname'])->addAttribute('name', 'Taken');
		
		$row += array('gridimage_id'=>$id);
		$image=new GridImage;
		$image->fastInit($row);
		$details = $image->getThumbnail(120,120,2);
		$sxe_item->addChild('Attribute', $details['server'].$details['url'])->addAttribute('name', 'Thumbnail');
		
		/*$sxe_item->addChild('Attribute', $row['imagetaken'])->addAttribute('name', 'Taken');
		$sxe_item->addChild('Attribute', $row['realname'])->addAttribute('name', 'Contributor');
		$sxe_item->addChild('Attribute', $row['imageclass'])->addAttribute('name', 'Category');*/
		
		
	}

	$sxe_relations = $sxe_data->addChild('ItemRelations');
	$sxe_relations->addAttribute('directed', 'false');
	
	$sxe_groups = $sxe_data->addChild('GroupRelations');
	foreach ($relations as $c => $row) {
		$ret = $sxe_groups->addChild('GroupRelation');
			$ret->addAttribute('itmId', $row[0]);
			$ret->addAttribute('grpId', $row[1]);
		$ret->addChild('Attribute', $row[2])->addAttribute('name', 'Type');
	}
	

###################

if (empty($_GET['html'])) {
	header("Content-type: text/xml");
	echo $sxe->asXML();
} else {
	$dom = dom_import_simplexml($sxe)->ownerDocument;
	$dom->formatOutput = true;

	print "<pre>";
	echo htmlentities(_utf8_decode($dom->saveXML()));	
	print "</pre>";
}
?>
