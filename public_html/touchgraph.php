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

	$sql = "SELECT gridimage_id,grid_reference,title,realname,user_id,imageclass,imagetaken
	FROM gridimage_search gi
	WHERE $sql_where
	LIMIT 25"; ##limt just to make sure

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	$photos = $db->getAssoc($sql);

}

###################

$relations = array();
foreach ($photos as $id => $row) {
	$relations[] = array($id,$row['imageclass']);
	$relations[] = array($id,$row['realname']);
	$relations[] = array($id,$row['imagetaken']);
}

###################

$sxe = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><TouchGraph_Navigator Version="1.0"></TouchGraph_Navigator>');

###########
$sxe_config = $sxe->addChild('Configuration');
	$sxe_names = $sxe_config->addChild('Names');
	$sxe_names->addChild('ItemName','Photo');
	$sxe_names->addChild('ItemIdName','Photo');
	$sxe_names->addChild('GroupName','Category');
	
	$sxe_att = $sxe_config->addChild('Attributes');
	$att = $sxe_att->addChild('Attribute');
		$att->addAttribute('name', 'Title');
		$att->addAttribute('Type', 'Text');
	$att = $sxe_att->addChild('Attribute');
		$att->addAttribute('name', 'URL');
		$att->addAttribute('Type', 'Text');

###########
$sxe_data = $sxe->addChild('Data');

	$sxe_items = $sxe_data->addChild('Items');
	foreach ($photos as $id => $row) {
		$sxe_item  = $sxe_items->addChild('Item');
		$sxe_item->addAttribute('id', $id);
		$sxe_item->addChild('Attribute', $row['title'])->addAttribute('name', 'Title');
		$sxe_item->addChild('Attribute', "http://".$_SERVER['HTTP_HOST']."/photo/".$id)->addAttribute('name', 'URL');
	}

	$sxe_relations = $sxe_data->addChild('ItemRelations');
	$sxe_relations->addAttribute('directed', 'false');
	foreach ($relations as $c => $row) {
		$ret = $sxe_relations->addChild('GroupRelation');
			$ret->addAttribute('itmId', $row[0]);
			$ret->addAttribute('grpId', $row[1]);
	}
	
	$sxe_groups = $sxe_data->addChild('GroupRelations');


###################

if (empty($_GET['html'])) {
	header("Content-type: text/xml");
	echo $sxe->asXML();
} else {
	$dom = dom_import_simplexml($sxe)->ownerDocument;
	$dom->formatOutput = true;

	print "<pre>";
	echo htmlentities($dom->saveXML());	
	print "</pre>";
}
?>
