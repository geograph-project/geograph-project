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

dieUnderHighLoad(2);

customGZipHandlerStart();

if (!empty($_GET['tab'])) {
	//need to connect before outputing HTML , as may need to add a HTTP headers

	if (!empty($CONF['db_read_connect2'])) {
	        if (!empty($DSN_READ))
	                $DSN_READ = str_replace($CONF['db_read_connect'],$CONF['db_read_connect2'],$DSN_READ);
	        if (!empty($CONF['db_read_connect']))
	                $CONF['db_read_connect'] = $CONF['db_read_connect2'];
	}

	$db = GeographDatabaseConnection(true);
	if ($db && $db->readonly) {//if Not readonly, then its just an error message
		customExpiresHeader(3600*23,false,true);
	}
}

$smarty->assign("page_title",'Your Photos around the site');

//we dont use smarty caching because the page is so big!
$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));


$tabs = array('featured'=>'Featured Images','collection'=>'In Collections','collection2'=>'Collection Image','forum-top'=>'Forum/Galleries Latest','forum'=>'Forum/Galleries All','search'=>'Marked Lists','thumbed'=>'Thumbed','gallery'=>'Showcase Gallery','photos'=>'Other Photos','viewed'=>'Most Viewed');

if (!empty($_GET['tab']) && $db->getOne("SHOW TABLES LIKE 'os_open_places'"))
	$tabs['settlement'] = "First in Settlement";


print "<div class=\"interestBox\" style=\"background-color:yellow\">";
print "<span style=color:red>NEW!</span> To enable email notifications for most of the items on this page, please see <a href=\"/profile.php?notifications=1\">this page</a>";
print "</div><br/><br/>";

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
print "<p>NOTE: Updated daily, please don't use Browse refresh/reload function.</p>";



if (empty($_GET['tab'])) {
	print "<p>Select a tab above</p>";
} elseif ($db && $db->readonly) {
	print "<div style=\"float:right\">Generated ".date('r')."</div>";

	$u = intval($USER->user_id);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	if ($_GET['tab'] == 'featured') {

		$t = 0;
		$sql = "SELECT gridimage_id gid,title t,showday s FROM gridimage_search INNER JOIN gridimage_daily USING (gridimage_id) WHERE showday IS NOT NULL AND showday < NOW() AND user_id = $u ORDER BY showday";

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Photograph of the Day</h3>";
				print "<ul>";
				$t=1;
			}

			print "<li>{$r['s']} <a href=\"/photo/{$r['gid']}\">".htmlentities2($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($t) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'gallery') {

		$t = 0;
		$sql = "SELECT gridimage_id gid,title t,showday FROM gridimage_search INNER JOIN gallery_ids i ON (id = gridimage_id) WHERE user_id = $u AND i.baysian > 3.3 ORDER BY i.baysian DESC";

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3><a href=\"http://www.geograph.org/gallery.php\">Showcase Gallery</a></h3>";

				print "<a href=\"/browser/#!/content_id=1/q=user{$USER->user_id}/realname+%22".urlencode($USER->realname)."%22\">View these images in Browser</a>";
				print "<ul>";
				$t=1;
			}

			print "<li>{$r['s']} <a href=\"/photo/{$r['gid']}\">".htmlentities2($r['t'])."</a>";
			if (!empty($r['showday']))
				print " (Featured {$r['showday']})";
			print "</li>";

			$recordSet->MoveNext();
		}

		if ($t) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'settlement') {


		print "<p>NOTE: We only have an accurate enough gazatteer for Great Britain. Isle of Man and Ireland, not yet included</p>";

		$t = $l = '';
		$sql = "SELECT gridimage_id,region,name1,name2,local_type,county_unitary,grid_reference,title from os_open_places inner join gridimage_search on (gridimage_id = first) where user_id = $u order by region,most_detail_view_res desc,name1";

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>First Photo in the Settlement</h3>";
				$t=1;
			}
			if ($r['region'] != $l) {
				if ($l) { print "</ul>"; }

				print "<b>".htmlentities2($r['region'])."</b>:";
				$l = $r['region'];
			}

			print "<li><a href=\"/photo/{$r['gridimage_id']}\">".htmlentities2($r['title'])."</a>";
			print " in <b>".htmlentities2($r['name1'].($r['name2']?" / {$r['name2']}":'').', '.$r['county_unitary'])."</b> ({$r['local_type']})</li>";

			$recordSet->MoveNext();
		}

		if ($l) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'collection') {

		$t = $l = '';
		$sql = "SELECT gi.gridimage_id gid,gi.title t,url,c.title ct,source FROM gridimage_search gi INNER JOIN gridimage_content USING (gridimage_id) INNER JOIN content c USING (content_id) WHERE gi.user_id = $u ORDER BY content_id";

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Used in Collections</h3>";
				$t=1;
			}
			if ($r['url'] != $l) {
				if ($l) { print "</ul>"; }

				print "<b><a href=\"{$r['url']}\">".htmlentities2($r['ct'])."</a></b> {$r['source']} contains:";
				$l = $r['url'];
			}

			print "<li><a href=\"/photo/{$r['gid']}\">".htmlentities2($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($l) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'collection2') {

		$t = $l = '';
		$sql = "SELECT gi.gridimage_id gid,gi.title t,url,c.title ct,source FROM gridimage_search gi INNER JOIN content c USING (gridimage_id) WHERE gi.user_id = $u AND source != 'snippet' and source != 'portal' ORDER BY content_id";

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Used as Collection image</h3>";
				$t=1;
			}
			if ($r['url'] != $l) {
				if ($l) { print "</ul>"; }

				print "<b><a href=\"{$r['url']}\">".htmlentities2($r['ct'])."</a></b> {$r['source']} uses:";
				$l = $r['url'];
			}

			print "<li><a href=\"/photo/{$r['gid']}\">".htmlentities2($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($l) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'forum' || $_GET['tab'] == 'forum-top') {

		print "<form method=get>";
		print "<input type=hidden name=tab value={$_GET['tab']}>";
		print "Filter: <select name=forum_id onchange=this.form.submit()>";
		print "<option value=0>All Forums</option>";
		$all = $db->getAll("select forum_id,forum_name from geobb_forums order by forum_order");
		foreach ($all as $row)
			printf('<option value="%d"%s>%s</option>',$row['forum_id'],$row['forum_id'] == @$_GET['forum_id']?' selected':'',htmlentities($row['forum_name']));
		print "</select></form>";


		$and = '';
		if (!empty($_GET['forum_id'])) {
			$and = " AND forum_id = ".intval($_GET['forum_id']);
		}

		$t = $l = '';

		if ($_GET['tab'] == 'forum-top') {
			$sql = "SELECT gridimage_id gid,title t,topic_id tid,topic_title as tt,post_id p ,forum_id f FROM gridimage_search INNER JOIN gridimage_post USING (gridimage_id) INNER JOIN geobb_topics USING (topic_id) WHERE user_id = $u $and ORDER BY post_id DESC limit 200";
		} else {
			$sql = "SELECT gridimage_id gid,title t,topic_id tid,topic_title as tt,post_id p ,forum_id f FROM gridimage_search INNER JOIN gridimage_post USING (gridimage_id) INNER JOIN geobb_topics USING (topic_id) WHERE user_id = $u $and ORDER BY topic_id,post_id";
		}

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Used in Forum Threads/Galleries</h3>";
				$t=1;
			}
			if ($r['tid'] != $l) {
				if ($l) { print "</ul>"; }

				print "<b><a href=\"/discuss/?action=vthread&amp;topic={$r['tid']}\">".($r['tt'])."</a></b> contains:"; //the forum already encodes entities
				$l = $r['tid'];
				print "<ul>";
			}

			print "<li><a href=\"/photo/{$r['gid']}\">".htmlentities2($r['t'])."</a>";
			print " [<a href=\"/discuss/?action=vpost&amp;forum={$r['f']}&amp;topic={$r['tid']}&amp;post={$r['p']}\">post</a>]</li>";
			$recordSet->MoveNext();
		}

		if ($l) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'search') {

		print '<p>Shows the number of times an image has been saved in a seperate search, via the <a href="/article/The-Mark-facility">Mark facility</a>. Does not show why, or does it discount the same person saving the same list multiple times</p>';

		$t = '';
		$sql = "SELECT gridimage_id gid,title t,count(*) c FROM gridimage_search INNER JOIN gridimage_query USING (gridimage_id) WHERE user_id = $u GROUP BY gridimage_id HAVING c > 1 ORDER BY c DESC";

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Used in saved marked lists</h3>";
				print "<ol>";
				$t=1;
			}

			print "<li value=\"{$r['c']}\"><a href=\"/photo/{$r['gid']}\">".htmlentities2($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($t) { print "</ol>"; }

	} elseif ($_GET['tab'] == 'thumbed') {

		$t = '';
		$sql = "SELECT gridimage_id gid,title t,type s FROM gridimage_search INNER JOIN vote_stat ON (gridimage_id=id) WHERE user_id = $u AND type in ('img','desc') ORDER BY last_vote DESC";

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if (!$t) {
				print "<h3>Thumbed Images</h3>";
				print "<ul>";
				$t=1;
			}

			print "<li><a href=\"/photo/{$r['gid']}\">".htmlentities2($r['t'])."</a> [{$r['s']}]</li>";

			$recordSet->MoveNext();
		}

		if ($t) { print "</ul>"; }

	} elseif ($_GET['tab'] == 'viewed') {

		$t = '';
		$sql = "SELECT floor(ln(hits+hits_archive)) as eee,gridimage_id gid,title t,grid_reference r FROM gridimage_search INNER JOIN gridimage_log USING (gridimage_id) WHERE user_id = $u AND hits > 50 ORDER BY 1 DESC, gridimage_id DESC";

		$recordSet = $db->Execute($sql);
		while (!$recordSet->EOF) {
			$r = $recordSet->fields;
			if ($t == $r['eee']) {
				$c++;
				if ($c > 10) {
					$recordSet->MoveNext();
					continue;
				}
			} else {
				if (!$t) {
					print "<h3>Most Viewed Images</h3>";
					print "<p>This is only counting views of the 'photo page', not including views other areas including search, and the 'Browser' function</p>";
				} else {
					if ($c > 10)
						print "<li>... and ".($c-10)." more</li>";
					print "</ul>";
				}
				print "<b>".number_format(exp($r['eee']),0)."+ views</b>";
				print "<ul>";
				$t = $r['eee'];
				$c=1;
			}

			print "<li>{$r['r']} <a href=\"/photo/{$r['gid']}\">".htmlentities2($r['t'])."</a></li>";

			$recordSet->MoveNext();
		}

		if ($t) {
			if ($c > 10)
                                print "<li>... and ".($c-10)." more</li>";
			print "</ul>";
		}

	} elseif ($_GET['tab'] == 'photos') {

		print "<h3>Links to your images from others</h3>";

		$sql = "SELECT b.gridimage_id,from_gridimage_id,t.title,t.grid_reference,f.title as from_title,f.grid_reference as from_grid_reference,f.realname as from_realname FROM gridimage_backlink b inner join gridimage_search t on (t.gridimage_id = b.gridimage_id) inner join gridimage_search f on (f.gridimage_id = b.from_gridimage_id) WHERE t.user_id = $u ORDER BY b.gridimage_id desc,from_gridimage_id desc";
		if (empty($_GET['all'])) {
			$sql = str_replace("ORDER BY", "AND f.user_id != $u ORDER BY", $sql);
			print "<a href=\"?tab=photos&all=1\">Show all photos (including your own)</a><hr/>";
		}

	        $recordSet = $db->Execute($sql);

	        $last = 0;
	        while ($recordSet && !$recordSet->EOF)
	        {
                	$row = $recordSet->fields;

        	        if ($last != $row['gridimage_id']) {
	                        if ($last) {
                        	        print "</ul>";
                	        }
        	                print "<b>{$row['grid_reference']} <a href=\"/photo/{$row['gridimage_id']}\">".htmlentities2($row['title'])."</a></b> is linked from:";
	                        print "<ul>";
	                }

        	        print "<li>{$row['from_grid_reference']} <a href=\"/photo/{$row['from_gridimage_id']}\">".htmlentities2($row['from_title'])."</a> by ".htmlentities2($row['from_realname'])."</li>";

	                $last = $row['gridimage_id'];
	                $recordSet->MoveNext();
	        }
	        print "</ul>";
	}

	if (!empty($recordSet) && $recordSet->numRows() === 0)
		print "<p>No Matching Images</p>";

} else {
	print "Site over capacity (or disabled due to server issues) - please try again tomorrow";
}


$smarty->display("_std_end.tpl");


