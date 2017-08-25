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
} elseif (isset($_GET['output']) && $_GET['output'] == 'alpha') {
	$template='tags_prefix.tpl';
} elseif (isset($_GET['output']) && $_GET['output'] == 'context') {
	$template='tags_prefix_subject.tpl';
} else {
	$template='statistics_table.tpl';
}


$cacheid='tags/prefix';
if (!empty($_GET['prefix'])) {
	$cacheid .= "|".md5($_GET['prefix']);
}
if (!empty($_GET['all'])) {
	$cacheid .= "|all";
}

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();

	$db = GeographDatabaseConnection(true);


	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

        $extra = array();

	if (empty($_GET['prefix'])) {
		$sql = "select substring_index(tagtext,':',1) as prefix,count(*) as tags,sum(count) as images from tag_stat where tagtext like '%:%' group by 1";

		if (!empty($_GET['all'])) {
			$title = "All Tag Prefixes";
			$link = " | Switch to <a href=?>Popular Prefixes</a>";
		} else {
			$title = "Popular Tag Prefixes";
			$sql .= " having avg(users) > 1.1 and tags > 3";
			$link = " | Switch to <a href=?all=1>All Prefixes</a>";
		}

		$table = $db->GetAll($sql);

		if (empty($_GET['output']))
			foreach($table as $idx => $row)
				$table[$idx]['prefix'] = '<a href="?prefix='.urlencode($row['prefix']).'">'.htmlentities($row['prefix'])."</a>";

		$smarty->assign('headnote', '<a href="/tags/">Back to Tags Homepage</a> '.$link.'<hr>');
		$smarty->assign('footnote', "Note: the images column is the total number of tags on all images, if an image has multiple prefix tags will be counted multiple times");
	} else {
		$q = $db->Quote($_GET['prefix']);
		$title = "[".htmlentities($_GET['prefix'])."] Prefixed Tags";

		$limit = 1500;
		if ($template=='tags_prefix_subject.tpl') {
			$sql = "SELECT tag,count as images,grouping,maincontext
			FROM tag_stat INNER JOIN tag USING (tag_id) INNER JOIN subjects ON (subject=tag) left join category_primary on (top = maincontext)
			WHERE prefix = $q AND status = 1 ORDER BY sort_order,subject";
		} else {
			$sql = "SELECT tag,count as images,description FROM tag_stat INNER JOIN tag USING (tag_id)
			WHERE prefix = $q AND status = 1 ORDER BY tag LIMIT $limit";
		}
		$table = $db->GetAll($sql);

		$p = urlencode2($_GET['prefix']).":";
		if ($template=='statistics_table.tpl')
			foreach($table as $idx => $row)
				$table[$idx]['tag'] = '<a href="/tagged/'.$p.urlencode2($row['tag']).'">'.htmlentities($row['tag'])."</a>";

		if (count($table) == $limit)
			$smarty->assign('footnote', "Note: Currently this table is limited to display $limit tags. There may be more");

		$views = (count($table) > 40)?array(''=>'In a Table','alpha'=>'Grouped by letter'):array();
		switch($_GET['prefix']) {
			case 'top': $message = '<tt>top</tt> is a special reserved prefix which we use for <a href="primary.php">Geographical Context</a>.'; break;
			case 'type': $message = '<tt>type</tt> is a special reserved prefix which we use for classifying images as per <a href="/article/Image-Type-Tags-update">Image Type Tags</a>. NOTE: Type Tags have only recently been introduced, so only recent submitted images currently have type these prefixed tags.'; break;
			case 'bucket': $message = '<tt>bucket</tt> prefix tags where an experiment in having specially defined and listed tags. Now mostly superceded by other taggins system, note in particular that only a small selection of images have had these tags assigned. <a href="/article/Image-Buckets">Read more about Bucket Tags</a>.'; break;
			case 'subject': $message = '<tt>subject</tt> is a special reserved prefix used to classify images by primary subject. The list of subject tags is fixed, and subject to moderation to add new ones.'; 
				$views['context'] = 'Grouped by Context'; break;
			case 'category': $message = '<tt>category</tt> has been used to mark <i>some</i> tags transformed from legacy category field, for the most part the prefix has no special meaning to the actual tag use.'; break;

			default: $message = '';
		}

		if (!empty($views)) {
			$message .= '<hr>View as'; $sep = " : ";
			foreach ($views as $key => $value) {
				if ($_GET['output'] == $key) {
					$message .= "$sep<b>$value</b>";
				} else {
					 $message .= "$sep<a href=\"?prefix=".urlencode($_GET['prefix'])."&output=$key\">$value</a>";
				}
				$sep = " / ";
			}
		}

		$smarty->assign('headnote', '<a href="/tags/">Back to Tags Homepage</a> &middot; <a href="/tags/prefix.php">Back to Prefix Listing</a><hr>'.$message);
		$smarty->assign('p',$p);

                $extra['prefix'] = $_GET['prefix'];

	}



	$smarty->assign_by_ref('table', $table);

	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));

        $smarty->assign_by_ref('extra',$extra);

}

$smarty->display($template, $cacheid);


