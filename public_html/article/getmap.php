<?php
/**
 * $Project: GeoGraph $
 * $Id: article.php 6904 2010-11-13 18:44:10Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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
$smarty->display('_std_begin.tpl');


$db = GeographDatabaseConnection(true);

ini_set('display_errors',1);

//todo, cache $i in memcache?

$mkey = intval($_GET['content_id']);

$i = $memcache->name_get('content2search',$mkey);

if (empty($i)) {
	if ($row = $db->getRow("SELECT content_id,source,title FROM content WHERE content_id= ".intval($_GET['content_id']))) { //todo, could also check has rows in 'gridiamge_content'!!?
		$data = array();
                $data['orderby'] = 'seq_id';
                $data['description'] = "in ".ucfirst($row['source']).": ".$row['title'];
		$data['searchq'] = "inner join gridimage_content using (gridimage_id) where content_id = {$row['content_id']}";

		//$data['resultsperpage'] = 250; --- anoyingly, this enfoices a limit of 100, so have to work aournd that.. 
		$before = $USER->search_results ?? null;
		$USER->search_results = 250;

                $data['adminoverride'] = 1; //this allows it as a 'Special' search

		$engine = new SearchEngineBuilder('#');
        	$i = $engine->buildAdvancedQuery($data,false);

		$memcache->name_set('content2search',$mkey,$i,$memcache->compress,$memcache->period_long*4);

		$USER->search_results = $before;
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		print "404";
		exit;
	}
}

if (!empty($i)) { ?>
	<h2>Auto-Updating Map Embed</h2>

	<p>This map will automatically update to include new images added to the live article.<br>
	 Loading upto 250 images at once (the first 250 used in the article)</p>

	<p>Map Embed Code: <tt style=padding:2px;background-color:#eee>[gmap=<? echo $i; ?>]</tt> (copy/paste into your article)

	<h3>Preview of the Map...<h3>

	<iframe src="/search.php?i=<? echo $i; ?>&amp;temp_displayclass=map_embed" width="750" height="430"></iframe>
<? }

$smarty->display('_std_end.tpl');

