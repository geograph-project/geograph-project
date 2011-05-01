<?php
/**
 * $Project: GeoGraph $
 * $Id: submissions.php 6368 2010-02-13 19:45:59Z barry $
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
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;



$USER->mustHavePerm("basic");

dieUnderHighLoad(1);

customGZipHandlerStart();
customExpiresHeader(3600*24,false,true);

$smarty->assign("page_title",'Your Photos around the site');

//we dont use smarty caching because the page is so big!
$smarty->display("_std_begin.tpl");

$tabs = array('featured'=>'Featured Images','collection'=>'In Collections','collection2'=>'Collection Image','forum'=>'Forum/Galleries','search'=>'Marked Lists','thumbed'=>'Thumbed',''=>'');

print "<div class=\"tabHolder\">";
foreach ($tabs as $name => $value) {
	if (!empty($_GET['tab']) && $_GET['tab'] == $name) {
		print "<a class=\"tabSelected nowrap\">$value</a> ";
	} else {
		print "<a class=\"tab nowrap\" href=\"?tab=$name\">$value</a> ";
	}
}
print "</div>";
	
print "<div class=\"interestBox\">";
print "<h3>Your Photos around the site</h3>";
print "</div>";
print "<p>NOTE: Please don't refresh this page more than once a day</p>";


$db = GeographDatabaseConnection(true);

if (empty($_GET['tab'])) {
	print "<p>Select a tab above</p>";
} elseif ($db->readonly) {
	$u = intval($USER->user_id);
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
		

	if ($_GET['tab'] == 'featured') {

		$t = 0;
		$sql = "SELECT gridimage_id gid,title t,showday s FROM gridimage_search INNER JOIN gridimage_daily USING (gridimage_id) WHERE showday IS NOT NULL AND user_id = $u ORDER BY showday";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			$r &= $recordSet->fields;
			if (!$t) {
				print "<h3>Photograph of the Day</h3>";
				print "<ul>";
				$t=1;
			}

			print "<li>{$r['s']} <a href=\"/photo/{$r['gid']}\">".htmlentities($r['title'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($t) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'collection') {

		$t = $l = 0;
		$sql = "SELECT gi.gridimage_id gid,gi.title t,url,c.title ct FROM gridimage_search gi INNER JOIN gridimage_content USING (gridimage_id) INNER JOIN content c USING (content_id) WHERE gi.user_id = $u ORDER BY content_id";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Used in Collections</h3>";
				$t=1;
			}
			if ($r['url'] != $l) {
				if ($l) { print "</ul>"; }

				print "<h4><a href=\"{$r['url']}\">".htmlentities($r['ct'])."</a></h4>";
				$l = $r['url'];
			}

			print "<li><a href=\"/photo/{$r['gid']}\">".htmlentities($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($l) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'collection2') {

		$t = $l = 0;
		$sql = "SELECT gi.gridimage_id gid,gi.title t,url,c.title ct FROM gridimage_search gi INNER JOIN content c USING (gridimage_id) WHERE gi.user_id = $u AND source != 'snippet' ORDER BY content_id";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Used as Collection image</h3>";
				$t=1;
			}
			if ($r['url'] != $l) {
				if ($l) { print "</ul>"; }

				print "<h4><a href=\"{$r['url']}\">".htmlentities($r['ct'])."</a></h4>";
				$l = $r['url'];
			}

			print "<li><a href=\"/photo/{$r['gid']}\">".htmlentities($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($l) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'forum') {

		$t = $l = 0;
		$sql = "SELECT gridimage_id gid,title t,topic_id tid,topic_title as tt FROM gridimage_search INNER JOIN gridimage_post USING (gridimage_id) INNER JOIN geobb_topics USING (topic_id) WHERE user_id = $u ORDER BY topic_id,post_id";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Used in Forum Threads/Galleries</h3>";
				$t=1;
			}
			if ($r['tid'] != $l) {
				if ($l) { print "</ul>"; }

				print "<h4><a href=\"/discuss/?action=vthread&amp;topic={$r['tid']}\">".($r['tt'])."</a></h4>"; //the forum already encodes entities
				$l = $r['tid'];
			}

			print "<li><a href=\"/photo/{$r['gid']}\">".htmlentities($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($l) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'search') {

		$t = $l = 0;
		$sql = "SELECT gridimage_id gid,title t,count(*) c FROM gridimage_search INNER JOIN gridimage_query USING (gridimage_id) WHERE user_id = $u GROUP BY gridimage_id HAVING c > 1 ORDER BY c DESC";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Used in saved marked lists</h3>";
				print "<ol>";
				$t=1;
			}

			print "<li value=\"{$r['c']}\"><a href=\"/photo/{$r['gid']}\">".htmlentities($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($l) { print "</ol>"; }

	} elseif ($_GET['tab'] == 'thumbed') {

		$t = $l = 0;
		$sql = "SELECT gridimage_id gid,title t FROM gridimage_search INNER JOIN vote_stat ON (gridimage_id=id) WHERE user_id = $u AND type in ('img','desc') ORDER BY last_vote DESC";

		$recordSet = &$db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Thumbed Images</h3>";
				print "<ul>";
				$t=1;
			}

			print "<li><a href=\"/photo/{$r['gid']}\">".htmlentities($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($l) { print "</ul>"; }

	} 
	
} else {
	print "Site over capacity - please try again tomorrow";
}


$smarty->display("_std_end.tpl");


