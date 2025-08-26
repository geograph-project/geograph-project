<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

	$db = GeographDatabaseConnection(true);
	$where = '';
	$andwhere = '';

	if (empty($_GET['prefix']) && empty($_GET['c'])) //dont really want ot list ALL tags!
		$_GET['prefix'] = 'subject';

	if (!empty($_GET['prefix'])) {
		if ($_GET['prefix'] == 'none') {
			$andwhere = " AND prefix = ''";
		} else {
			$andwhere = " AND prefix = ".$db->Quote($_GET['prefix']);
		}
		$smarty->assign('theprefix', $_GET['prefix']);
	}

	if (!empty($_GET['c'])) {
		$andwhere .= " AND classification = ".$db->Quote($_GET['c']);
	} elseif (!empty($_GET['n'])) {
		$andwhere .= " AND (classification LIKE 'named%' OR classification IN ('related-to','branded-object'))"; //maybe branded-object shoudl of been 'named-brand'
	}

	if (!empty($_GET['o'])) {
		$andwhere .= " AND canonical=0"; //only really works in specific prefixes!
	}


$smarty->display('_std_begin.tpl');

print "<h2>Categorized Tags</h2>";
print "<div style=max-width:60em>";

$tags = $db->getAll("SELECT tag_id,canonical=0 as offical,prefix,tag,count,classification FROM tag_stat INNER JOIN tag USING (tag_id) WHERE classification IS NOT NULL
	$andwhere
	ORDER BY classification, tag, count desc LIMIT 10000");

$last = "";
foreach($tags as $row) {
	if ($row['classification'] == 'unsafe')
		continue;

	if ($last != $row['classification']) {
		print "<h3>".htmlentities($row['classification'])."</h3> &middot; ";
	}
	$last = $row['classification'];
	print "<span class=nowrap>";
	if (!empty($row['prefix']))
		$url = "/tagged/".urlencode2($row['prefix']).":".urlencode2($row['tag']);
	else
		$url = "/tagged/".urlencode2($row['tag']);
	if (!empty($row['offical'])) //only applies in certain prefixes!
		print "<b>";
	print "<a href=\"$url\">".htmlentities($row['tag'])."</a>";
	if (!empty($row['offical']))
		print "</b>";
	print "[".number_format($row['count'])."]";
	print "</span> &middot; ";
}

print "</div>";

$smarty->display('_std_end.tpl');


