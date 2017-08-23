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

require_once('geograph/global.inc.php');

header('Access-Control-Allow-Origin: *');
customExpiresHeader(3600);

 $conv = new Conversions;

$sql = array();
$sql['wheres'] = array();
$sphinxq = empty($_GET['q'])?'':$_GET['q'];

if (!empty($_GET['olbounds'])) {
        $b = explode(',',trim($_GET['olbounds']));
                #### example: -10.559026590196122,46.59604915850878,7.514135843906623,54.84589681367314

        $span = max($b[2] - $b[0],$b[3] - $b[1]);
	$maxspan = empty($_GET['user_id'])?0.2:0.35;
	if (!empty($sphinxq))
		$maxspan = 1;

        if ($span > $maxspan) {
                $error = "Zoom in closer to the British Isles to see coverage details";
        } else {

		$sql['wheres'][] = "wgs84_lat BETWEEN ".deg2rad($b[1])." AND ".deg2rad($b[3]);
		$sql['wheres'][] = "wgs84_long BETWEEN ".deg2rad($b[0])." AND ".deg2rad($b[2]);

		//todo, add myridas!
	}

} elseif (!empty($_GET['bounds'])) {
	$b = str_replace('Bounds','',$_GET['bounds']);
	$b = str_replace('(','',$b);
	$b = str_replace(')','',$b);

	$b = explode(',',$b);

	$span = max($b[3] - $b[1],$b[2] - $b[0]);

	//TODO!
	#$ire = ($lat > 51.2 && $lat < 55.73 && $long > -12.2 && $long < -4.8);
	#$uk = ($lat > 49 && $lat < 62 && $long > -9.5 && $long < 2.3);

	if ($span > 0.28) {
		$error = "Zoom in closer to the British Isles to see coverage details";
	} else {

                $sql['wheres'][] = "wgs84_lat BETWEEN ".deg2rad($b[0])." AND ".deg2rad($b[2]);
                $sql['wheres'][] = "wgs84_long BETWEEN ".deg2rad($b[1])." AND ".deg2rad($b[3]);
	}

} else {
	$error = "unsupported method";
}

#print "<pre>";
#print_r($error);
#print_r($sql);
#exit;


if (empty($error)) {

	$sph = GeographSphinxConnection('sphinxql',true);

	$sql['tables'] = array();
	$sql['tables']['8'] = 'sample8';
	$sql['columns'] = 'scenti as s,count(*) as c';
	$sql['group'] = 'scenti';
	$sql['order'] = 'c desc';
	$sql['option'] = 'ranker=none';
	$sql['limit'] = 1000;

	if (!empty($_GET['user_id'])) {
                $sphinxq .= " @user user".intval($_GET['user_id']);
	}
	if (!empty($_GET['myriads']) && preg_match('/^\w+(,\w+)*$/',$_GET['myriads'])) {
		$myriads = explode(',',$_GET['myriads']);
                $sphinxq .= " @myriad (".implode('|',array_unique($myriads)).")";
	}

	if (!empty($sphinxq)) {
		$sql['wheres'][] = "MATCH(".$sph->Quote($sphinxq).")";
	}

	if (true) {
		$crit = new SearchCriteria;
		$start = $crit->toDays("DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR))");
		$sql['columns'] .= ",SUM(IF(takendays>$start,1,0)) as r";
	}


	$query = sqlBitsToSelect($sql);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$rows = $sph->getAll($query);

	foreach ($rows as $idx => $row) {
		$rows[$idx]['c'] = intval($row['c']);
		if (true)
			$rows[$idx]['r'] = intval($row['r']);

#(gi.reference_index * 1000000000 + IF(g2.natgrlen+0 <= 3,(g2.nateastings DIV 100) * 100000 + (g2.natnorthings DIV 100),0)) AS scenti \
#| 1277703751 |        1 |
#  0123456789
#  GEEEENNNNN

		$ri = substr($row['s'],0,1);
		$e = (substr($row['s'],1,4)*100)+50;
		$n = (substr($row['s'],5,5)*100)+50;

		list($lat,$lng) = $conv->national_to_wgs84($e,$n,$ri);
		$rows[$idx]['lat'] = round($lat,6);
		$rows[$idx]['lng'] = round($lng,6);

		$rows[$idx]['gr'] = $rows[$idx]['s']; //todo/tofix temp
		unset($rows[$idx]['s']);
	}
	$data = array('markers'=>$rows);

	$info = $sph->getAssoc("SHOW META");
        if (!empty($info['total_found'])) {
		$data['count'] = $info['total_found'];
	}

} else {
	$data = array('error'=>$error);
}

outputJSON($data);
