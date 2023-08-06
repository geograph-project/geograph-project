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


if (!empty($_GET['json'])) {
	$data = array();
	$data['nodes'] = array();
	$data['links'] = array();

        $db = GeographDatabaseConnection(true);
	$tops = $db->getAll("select grouping,top from category_primary order by sort_order");
	$group = 0;
	$last = null;
	foreach ($tops as $row) {
		if ($last != $row['grouping'])
			$group++;
		$last = $row['grouping'];
		$data['nodes'][] = array('id'=> $row['top'], 'group' => $group);
	}

	$h = fopen('/var/www/geograph/count-top-pairs.txt','r');
	while ($h && !feof($h)) {
		$line = trim(fgets($h));
		$bits = explode(' | ',$line);
		if (count($bits) == 3) {
			if ($bits[0] >10000)
				$data['links'][] = array('source'=>$bits[1], 'target'=>$bits[2],
					'value'=>1000-intval(log($bits[0])*100)
				);
		}
	}

	outputJSON($data);
	exit;
}
?>


<head>
  <style> body { margin: 0; } </style>

  <script src="//unpkg.com/force-graph"></script>
  <!--<script src="../../dist/force-graph.js"></script>-->
</head>

<body>
  <div id="graph"></div>

  <script>
    fetch('/stuff/count-top-pairs.php?json=1').then(res => res.json()).then(data => {
      const Graph = ForceGraph()
      (document.getElementById('graph'))
        .graphData(data)
        .nodeId('id')
        .nodeVal('val')
        .nodeLabel('id')
        .nodeAutoColorBy('group')
        .linkSource('source')
        .linkTarget('target')
    });
  </script>
</body>
