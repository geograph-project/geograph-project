<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;
}

init_session();

$smarty = new GeographPage;

if (empty($_GET['api'])) {
$USER->mustHavePerm('basic');

$smarty->display('_std_begin.tpl');


?>
<script src="/sorttable.js"></script>
<?
}

	$db2=NewADOConnection($CONF['filesystem_dsn']);

	$db=GeographDatabaseConnection(false);
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_GET['update'])) {
	$updated = $db2->getOne("select CREATE_TIME from information_schema.tables where table_name = 'file_list_by_image'");

	$images = $db->getOne("SELECT COUNT(*) FROM gridimage");
	$large = $db->getOne("SELECT COUNT(*) FROM gridimage_size where original_width > 0");

	$data = $db2->getAll("select class,count(*) images,sum(replica_count>1) images_replica,sum(backup_count>1) images_backup from file_list_by_image group by class order by null");

	foreach ($data as $row) {
		$updates = array();
		$updates['updated'] = $updated;
		$title = ($row['class'] == 'full.jpg')?'Full Size Images':'Larger Uploads';
		$updates['target'] = ($row['class'] == 'full.jpg')?$images:$large;
		$updates['unit'] = 'images';

		$updates['title'] = "$title :: In FileSystem";
		$updates['value'] = $row['images'];
		$db->Execute('REPLACE INTO systemstatus SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

		$updates['title'] = "$title :: Replicated to Multiple Servers";
                $updates['value'] = $row['images_replica'];
                $db->Execute('REPLACE INTO systemstatus SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

		$updates['title'] = "$title :: In Multiple Off-Site backups";
                $updates['value'] = $row['images_backup'];
                $db->Execute('REPLACE INTO systemstatus SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	}
}



	$where = '';
	print "<h2>System Status</h2>";

	$rows = $db->getAll("SELECT * FROM systemstatus WHERE status = 'active' $where");
	print "<form method=post><table class=\"report sortable\" id=\"photolist\" border=1 cellspacing=0 cellpadding=4>";


	print "<thead><tr>";
	print "<td>Title</td>";
	print "<td>Last Check</td>";
	print "<td>Value</td>";
	print "<td>Unit</td>";
	print "<td>Target</td>";
	print "<td>Percentage</th></tr></thead><tbody>";
	foreach ($rows as $row) {
		print "<tr>";
		print "<td>".htmlentities($row['title'])."</td>";
		print "<td>".htmlentities($row['updated'])."</td>";
		print "<td>".htmlentities($row['value'])."</td>";
		print "<td>".htmlentities($row['unit'])."</td>";
		print "<td>".htmlentities($row['target'])."</td>";
		if ($row['target'] > 0) {
			$percent = $row['value']/$row['target']*100.0;
			printf("<td>%.1f</td>",$percent);
		}
		print "</tr>";
	}
	print "</tbody></table>";



$smarty->display('_std_end.tpl');

