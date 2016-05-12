<?php
/**
 * $Project: GeoGraph $
 * $Id: images.php 6629 2010-04-13 21:07:14Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_GET['prefix']) && $_GET['prefix'] == 'top' && empty($_GET['output'])) {
	include "primary.php";
	exit;
}

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
	$template='statistics_table.tpl';
}

$cacheid='tags/prefix';
if (!empty($_GET['prefix'])) {
	$cacheid .= "|".md5($_GET['prefix']);
}

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();

	$db = GeographDatabaseConnection(true);


	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	if (empty($_GET['prefix'])) {
		$title = "Tags by Prefix";
		$sql = "select substring_index(tagtext,':',1) as prefix,count(*) as tags,sum(count) as images from tag_stat where tagtext like '%:%' group by 1 having avg(users) > 1.1 and tags > 3";

		$table = $db->GetAll($sql);

		foreach($table as $idx => $row)
			$table[$idx]['prefix'] = '<a href="?prefix='.urlencode($row['prefix']).'">'.htmlentities($row['prefix'])."</a>";

		$smarty->assign('headnote', '<a href="/tags/">Back to Tags Homepage</a><hr>');
		$smarty->assign('footnote', "Note: the images column is the total number of tags on all images, if an image has multiple prefix tags will be counted multiple times");
	} else {
		$q = $db->Quote($_GET['prefix']);
		$title = "[".htmlentities($_GET['prefix'])."] Prefixed Tags";

		$sql = "SELECT tag,count as images,description FROM tag_stat INNER JOIN tag USING (tag_id) WHERE prefix = $q AND status = 1 AND count > 1 ORDER BY tag LIMIT 1000";

		$table = $db->GetAll($sql);

		$p = urlencode2($_GET['prefix']).":";
		foreach($table as $idx => $row)
			$table[$idx]['tag'] = '<a href="/tagged/'.$p.urlencode2($row['tag']).'">'.htmlentities($row['tag'])."</a>";

		if (count($table) == 1000)
			$smarty->assign('footnote', "Note: Currently this table is limited to display 1000 tags. There may be more");

		switch($_GET['prefix']) {
			case 'top': $message = '<tt>top</tt> is a special reserved prefix which we use for <a href="primary.php">Geographical Context</a>'; break;
			case 'type': $message = '<tt>type</tt> is a special reserved prefix which we use for classifingin images as per <a href="/article/Image-Type-Tags">Image Type Tags</a>'; break;
			case 'bucket': $message = '<tt>bucket</tt> prefix tags where an experiment in having specially defined and listed tags. Now mostly superceded by other taggins system, note in particular that only a small selection of images have had these tags assigned. <a href="/article/Image-Buckets">Read more about Bucket Tags</a>'; break;
			case 'subject': $message = '<tt>subject</tt> is a special reserved prefix used to classify images by primary subject. The list of subject tags is fixed, and subject to moderation to add new ones'; break;
			case 'category': $message = '<tt>category</tt> has been used to mark <i>some</i> tags transformed from legacy category field, for the most part the prefix has no special meaning to the actual tag use'; break;

			default: $message = '';
		}
		$smarty->assign('headnote', '<a href="/tags/">Back to Tags Homepage</a> &middot; <a href="/tags/prefix.php">Back to Prefix Listing</a><hr>'.$message);
	}



	$smarty->assign_by_ref('table', $table);

	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));


}

$smarty->display($template, $cacheid);


#########################################
# functions!

function urlencode2($input) {
        return str_replace(array('%2F','%3A','%20'),array('/',':','+'),urlencode($input));
}

