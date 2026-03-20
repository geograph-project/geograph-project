<?php
/**
 * $Project: GeoGraph $
 * $Id: clusters.php 5786 2009-09-12 10:18:04Z barry $
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

//This file 'duplicates' the function of $gazetter->findListByNational(..) but is a simpler streamlined implemantion, using a direct Sphinx Index.
	//... its not a actual 'fulltext search', just that sphinx still works well for a attribute lookups, full-table scans are in memory

require_once('geograph/global.inc.php');

//header('Access-Control-Allow-Origin: *');
customExpiresHeader(3600*24);

 $conv = new Conversions;

$sql = array();
$sql['wheres'] = array();

	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$sphinxq = empty($_GET['q'])?'':$_GET['q'];
if (!empty($_GET['q'])) {
	 $sql['wheres'][] = "MATCH(".$sph->Quote($_GET['q']).")";

	//perhaps could allow both? q+e/n

} elseif (!empty($_GET['e']) && !empty($_GET['n'])) {
	$x = intval($_GET['e']);
	$sql['wheres'][] = "mbr_xmin < $x and mbr_xmax > $x";

	$y = intval($_GET['n']);
	$sql['wheres'][] = "mbr_ymin < $y and mbr_ymax > $y";
} else {
	$error = "unknown query";
}

//todo, add ri = 1/2 (because we about to add ireland to the index!!)

if (empty($error)) {


	$sql['tables'] = array();
	$sql['tables'][] = 'os_open_names';
	$sql['columns'] = 'name1,name2,local_type,geometry_x,geometry_y,min(least_detail_view_res) as sorter';
	$sql['group'] = 'name1,name2';
	$sql['order'] = 'sorter ASC';
	$sql['limit'] = 100;
	$sql['option'] = 'ranker=none';

	$query = sqlBitsToSelect($sql);

	if (!empty($_GET['v'])) {
		//just to make more compatible with places.json!
		$data['items'] = $sph->getAll($query);

		$count = count($data['items']);
		$query = htmlentities($_GET['q']);
		$info = $sph->getAssoc("SHOW META");
		$data['total_found'] = $info['total_found'];
		$data['query_info'] = "Query '$query' retrieved $count of {$info['total_found']} matches in {$info['time']} sec.\n";
	} else {

		$data['rows'] = $sph->getAll($query);

		$info = $sph->getAssoc("SHOW META");
	        if (!empty($info['total_found']))
			$data['count'] = $info['total_found'];
	}

	$data['copyright'] = "Contains OS data (c) Crown Copyright [and database right] 2021.";

} else {
	$data = array('error'=>$error);
}

outputJSON($data);
