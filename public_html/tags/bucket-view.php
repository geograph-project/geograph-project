<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 7069 2011-02-04 00:06:46Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_GET['i'])) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	
	
	$pg = intval($_GET['page']);
	
	$engine = new SearchEngine(intval($_GET['i']));

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	$images = $engine->ReturnAssoc($pg);
	
	if (!empty($images)) {
		$ids = implode(',',array_keys($images));
	
		$db = $engine->_getDB(true);
	
		$buckets = $db->getAll("SELECT gridimage_id,tag,COUNT(*) AS count FROM tag t INNER JOIN gridimage_tag gt USING (tag_id) WHERE prefix = 'bucket' AND t.status = 1 AND gt.status = 1 AND gridimage_id IN ($ids) GROUP BY tag_id,gridimage_id");
		$b = array();
		foreach ($buckets as $row) {
			$b[$row['tag']][$row['gridimage_id']] = $row['count'];
		}
		
		print "<table cellspacing=0 cellpadding=2 border=1><tr><td></td>";
		foreach ($b as $bucket => $d) {
			print "<th>$bucket</th>";
		}
		print "</tr>";
		foreach ($images as $gid => $image) {
			print "<tr>";
			print "<th><a href=\"/photo/$gid\">".htmlentities($image['title'])."</a></th>";
			
			
			foreach ($b as $bucket => $d) {
				print @"<td>".$b[$bucket][$gid]."</td>";
			}	
			print "</tr>";
		}
		print "</table>";
		
	} else {
		die("no results :(");
	}
}