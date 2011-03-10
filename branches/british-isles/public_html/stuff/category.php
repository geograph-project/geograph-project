<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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


$smarty = new GeographPage;

$template='stuff_category.tpl';
$cacheid='';

if (!empty($_GET['type']) && preg_match('/^(\w+)$/',$_GET['type'])) {
	
	$smarty->assign('type',$_GET['type']);
	
	$cacheid = $_GET['type'];
}

if (!empty($_GET['v']) && preg_match('/^(\w+)$/',$_GET['v'])) {
	
	$smarty->assign('v',$_GET['v']);
	
	$cacheid .= ".".$_GET['v'];
}

$types = array('dropdown' => 'Dropdown','autocomplete' => 'Auto Complete Text Box','canonical' => 'Canonical Dropdown','canonicalplus' => 'Canonical Dropdown + Optional Detail','canonicalmore' => 'Canonical Dropdown (full unmoderated list)','top'=>'Top Level Category + Tags');
$smarty->assign_by_ref('types',$types);

if ($_GET['type'] == 'top') {
$data = "
Landform
Mountain
Hill
Valley
Plateau
Slope
Glacial
Undulating
Flat
Coast & estuary
Geology

Environment
Woodland
Rough ground
Grassland
Lakes & wetland
Rivers & streams
Tidal
Air, Sky & Weather

Human use
Mining & Quarrying
Crops
Grazing
Forestry
Manufacturing & Construction
Commerce/Retail/Services
Energy production
Water supply
Drainage
Defence
Sport/Leisure/Entertainment
Mixed uses
Disposal & Degradation

Human habitat
Lowland countryside
Scattered
Country estate
Village
Suburb & fringe
Town & city
Transient
Open space
Social Customs & Events
Closed communities
Small-scale
Barriers & boundaries

Communications
Roads & road transport
Tracks and paths
Railways
Waterways
Docks & harbours
Air transport
Energy infrastructure
Telecommunications";
	$next = false;$group = '';
	$tops = array();
	foreach (explode("\n",str_replace("\r",'',$data)) as $line) {
		if (!$line) {
			$next = true;
		} elseif($next) {
			$group = $line;
			$next = false;
		} else {
			$tops[$group][] = $line;	
		}

	}
	$smarty->assign_by_ref('tops',$tops);
}

$smarty->display($template, $cacheid);

